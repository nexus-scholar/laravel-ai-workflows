# Tutorial 6: Production Patterns & Deployment (40 minutes)

In this final tutorial, you'll learn production-ready patterns: queue workers, checkpointing, error handling, and monitoring.

## Prerequisites

✅ Complete [Tutorial 5: RAG](./05-advanced-rag.md)  
✅ Familiar with Laravel queues  

## What You'll Learn

- Queue-safe graph execution
- Checkpointing and resumability
- Error handling and resilience
- Monitoring and observability

## Step 1: Queue-Safe Graph Execution

### Problem

Closures don't serialize for queue workers.

### Solution: Resolver Pattern

Create `app/Workflows/WorkflowRegistry.php`:

```php
<?php

namespace App\Workflows;

use Closure;

class WorkflowRegistry
{
    private array $workflows = [];

    public function register(string $key, Closure $builder): void
    {
        $this->workflows[$key] = $builder;
    }

    public function resolve(string $key)
    {
        if (!isset($this->workflows[$key])) {
            throw new RuntimeException("Workflow $key not registered");
        }

        return call_user_func($this->workflows[$key]);
    }
}
```

Register in service provider:

```php
// app/Providers/AppServiceProvider.php

use App\Workflows\WorkflowRegistry;
use Nexus\\Workflow\Graph\StateGraph;

public function register()
{
    app()->singleton(WorkflowRegistry::class, function () {
        $registry = new WorkflowRegistry();

        // Register workflows (builders are closures)
        $registry->register('slr', fn() => $this->buildSLRGraph());
        $registry->register('approval', fn() => $this->buildApprovalGraph());

        return $registry;
    });
}

private function buildSLRGraph(): StateGraph
{
    $graph = new StateGraph();
    // ... build nodes and edges
    return $graph;
}
```

Create a job:

```php
// app/Jobs/ExecuteGraphJob.php

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Workflows\WorkflowRegistry;

class ExecuteGraphJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private string $workflowKey,
        private array $initialState,
    ) {}

    public function handle(WorkflowRegistry $registry)
    {
        // Resolve graph from container (not serialized)
        $graph = $registry->resolve($this->workflowKey);

        // Reconstruct state from array
        $state = MyState::fromArray($this->initialState);

        // Execute
        $result = $graph->compile()->invoke($state);

        // Handle result
        event(new GraphCompleted($result));
    }
}
```

Dispatch it:

```php
// In controller
dispatch(new ExecuteGraphJob(
    workflowKey: 'slr',
    initialState: $initialState->toArray()
));
```

## Step 2: Checkpointing (Resumable Workflows)

Save state after each node for resumability:

```php
// app/Support/CheckpointedGraph.php

use Nexus\\Workflow\Graph\CompiledGraph;
use Nexus\\Workflow\Graph\State;
use Illuminate\Support\Facades\Cache;

class CheckpointedGraph
{
    private string $checkpointKey;

    public function __construct(
        private CompiledGraph $graph,
        private string $workflowId,
    ) {
        $this->checkpointKey = "checkpoint.$workflowId";
    }

    public function invoke(State $initialState): State
    {
        $current = $initialState;

        foreach ($this->graph->stream($current) as $nodeName => $nodeState) {
            // Save after each step
            $this->saveCheckpoint($nodeState);

            logger()->info("Graph step completed", [
                'workflow' => $this->workflowId,
                'node' => $nodeName,
            ]);

            $current = $nodeState;
        }

        return $current;
    }

    private function saveCheckpoint(State $state): void
    {
        Cache::put(
            $this->checkpointKey,
            $state->toArray(),
            ttl: 86400  // 24 hours
        );
    }

    public function resume(): State
    {
        $saved = Cache::get($this->checkpointKey);

        if (!$saved) {
            throw new RuntimeException('No checkpoint found');
        }

        return MyState::fromArray($saved);
    }

    public function hasCheckpoint(): bool
    {
        return Cache::has($this->checkpointKey);
    }
}
```

Use it:

```php
class GraphExecutor
{
    public function run(string $workflowId, State $state): State
    {
        $graph = app(WorkflowRegistry::class)->resolve('my_workflow');
        $checkpointed = new CheckpointedGraph($graph->compile(), $workflowId);

        try {
            return $checkpointed->invoke($state);
        } catch (Exception $e) {
            logger()->error('Workflow failed', ['error' => $e, 'id' => $workflowId]);

            // Can resume later
            throw $e;
        }
    }

    public function resume(string $workflowId): State
    {
        $graph = app(WorkflowRegistry::class)->resolve('my_workflow');
        $checkpointed = new CheckpointedGraph($graph->compile(), $workflowId);

        if (!$checkpointed->hasCheckpoint()) {
            throw new RuntimeException('No checkpoint to resume from');
        }

        $lastState = $checkpointed->resume();
        return $checkpointed->invoke($lastState);
    }
}
```

## Step 3: Error Handling & Resilience

### Try-Catch with Fallback

```php
class ResilientChain
{
    public function run(string $input): string
    {
        try {
            return $this->chain->run(['input' => $input]);
        } catch (RateLimitException $e) {
            logger()->warning('Rate limited');
            dispatch(new DelayedChainJob($input))->delay(60);
            return 'Your request is queued. You will receive a response soon.';
        } catch (NetworkException $e) {
            logger()->error('Network error', ['error' => $e]);
            return 'Temporary network issue. Please try again.';
        } catch (Exception $e) {
            logger()->error('Chain execution failed', [
                'error' => $e->getMessage(),
                'input' => $input,
                'trace' => $e->getTraceAsString(),
            ]);
            return 'An error occurred. Our team has been notified.';
        }
    }
}
```

### Error Recovery in Graphs

```php
final class ResilientWorkflow
{
    public function build()
    {
        $graph = new StateGraph();

        $graph->addNode('attempt', fn(WorkflowState $s) => {
            try {
                $result = riskyOperation($s->data);
                return $s->with(['result' => $result, 'error' => null]);
            } catch (Exception $e) {
                return $s->with([
                    'error' => $e->getMessage(),
                    'retries' => $s->retries + 1,
                ]);
            }
        });

        $graph->addNode('wait', fn(WorkflowState $s) => {
            $delay = 2 ** $s->retries;
            sleep($delay);
            return $s;
        });

        $graph->addNode('fail', fn(WorkflowState $s) =>
            $s->with(['result' => 'FAILED'])
        );

        $graph->setEntryPoint('attempt');

        $graph->addConditionalEdge('attempt', fn(WorkflowState $s) =>
            $s->error === null ? 'success' :
            ($s->retries < 3 ? 'wait' : 'fail')
        );

        $graph->addEdge('wait', 'attempt');
        $graph->addNode('success', fn($s) => $s);
        $graph->addEdge('success', StateGraph::END);
        $graph->addEdge('fail', StateGraph::END);

        return $graph;
    }
}
```

## Step 4: Monitoring & Logging

### Chain Execution Logging

```php
class LoggingChain implements ChainContract
{
    public function __construct(
        private ChainContract $chain,
        private string $name = 'Chain',
    ) {}

    public function run(array $inputs): mixed
    {
        $start = microtime(true);

        logger()->debug("[$this->name] Starting", [
            'inputs' => array_keys($inputs),
        ]);

        try {
            $result = $this->chain->run($inputs);
            $duration = microtime(true) - $start;

            logger()->info("[$this->name] Completed", [
                'duration_ms' => round($duration * 1000),
                'status' => 'success',
            ]);

            return $result;
        } catch (Exception $e) {
            logger()->error("[$this->name] Failed", [
                'duration_ms' => round((microtime(true) - $start) * 1000),
                'error' => $e->getMessage(),
                'inputs' => $inputs,
            ]);

            throw $e;
        }
    }

    public function stream(array $inputs): \Generator
    {
        logger()->debug("[$this->name] Streaming start");
        yield from $this->chain->stream($inputs);
    }

    // ... other methods
}

// Wrap chains
$chain = new LoggingChain($originalChain, 'SearchChain');
```

### Graph Tracing

```php
class TracingGraph
{
    private array $trace = [];

    public function invoke(State $state, CompiledGraph $graph): State
    {
        foreach ($graph->stream($state) as $nodeName => $nodeState) {
            $this->trace[] = [
                'node' => $nodeName,
                'timestamp' => now()->iso8601(),
                'duration_ms' => 0,  // Could measure per node
                'state_size' => strlen(json_encode($nodeState->toArray())),
            ];

            logger()->debug("Graph node completed", [
                'node' => $nodeName,
                'state_keys' => array_keys($nodeState->toArray()),
            ]);
        }

        return $nodeState;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }
}
```

## Step 5: Performance Optimization

### Caching Chain Results

```php
class CachingChain implements ChainContract
{
    public function __construct(
        private ChainContract $chain,
        private string $cachePrefix = 'chain',
        private int $ttl = 3600,
    ) {}

    public function run(array $inputs): mixed
    {
        $cacheKey = $this->cachePrefix . '.' . md5(json_encode($inputs));

        return cache()->remember($cacheKey, $this->ttl, fn() =>
            $this->chain->run($inputs)
        );
    }

    // ... other methods
}

// Use it
$chain = new CachingChain($originalChain, 'qa_cache', 3600);
```

### Batch Processing

```php
class BatchChainProcessor
{
    public function __construct(
        private ChainContract $chain,
        private int $batchSize = 10,
        private int $delaySecs = 1,
    ) {}

    public function processBatch(array $items): array
    {
        $results = [];
        $count = 0;

        foreach (array_chunk($items, $this->batchSize) as $batch) {
            foreach ($batch as $item) {
                try {
                    $results[] = $this->chain->run(['input' => $item]);
                    $count++;

                    if ($count % 100 === 0) {
                        logger()->info("Processed $count items");
                    }
                } catch (Exception $e) {
                    logger()->warning("Item failed", ['item' => $item]);
                    $results[] = null;
                }
            }

            // Rate limiting between batches
            sleep($this->delaySecs);
        }

        return $results;
    }
}

// Use it
$processor = new BatchChainProcessor($chain);
$results = $processor->processBatch($millionItems);
```

## Step 6: Metrics & Analytics

```php
class MetricsMiddleware
{
    public function __construct(private ChainContract $chain) {}

    public function run(array $inputs): mixed
    {
        $start = microtime(true);

        try {
            $result = $this->chain->run($inputs);
            $duration = microtime(true) - $start;

            metrics()->increment('chain.executions.success');
            metrics()->recordDuration('chain.duration_ms', $duration * 1000);

            return $result;
        } catch (Exception $e) {
            metrics()->increment('chain.executions.failed');
            throw $e;
        }
    }

    // ... other methods
}
```

## Step 7: Real-World: Complete System

```php
class ProductionSystem
{
    public function processDocument(string $documentId): void
    {
        // 1. Load document
        $document = Document::findOrFail($documentId);

        // 2. Create checkpointed workflow
        $registry = app(WorkflowRegistry::class);
        $graph = $registry->resolve('document_processing');
        $checkpointed = new CheckpointedGraph($graph->compile(), $documentId);

        // 3. Initial state
        $state = DocumentState::fromArray([
            'document' => $document->content,
            'status' => 'processing',
        ]);

        // 4. Execute with error handling
        try {
            $result = $checkpointed->invoke($state);

            // 5. Save results
            $document->update([
                'analysis' => json_encode($result->toArray()),
                'status' => 'completed',
            ]);

            event(new DocumentProcessed($document));
        } catch (Exception $e) {
            // 6. Checkpoint allows resume
            $document->update(['status' => 'failed']);
            logger()->error('Document processing failed', ['id' => $documentId]);

            // Re-queue with delay
            dispatch(new ProcessDocumentJob($documentId))
                ->delay(now()->addHours(1));
        }
    }
}
```

## Exercises

### Exercise 1: Resilient RAG Service
Build a RAG system that:
- Retries on network failures
- Caches results
- Logs all operations

```php
// 👉 Your code here
```

### Exercise 2: Monitored Workflow
Build a workflow that:
- Tracks execution time per node
- Logs state transitions
- Reports metrics

```php
// 👉 Your code here
```

### Exercise 3: Batch Processing Pipeline
Build a system that:
- Processes 100K items
- Batches requests to avoid rate limits
- Checkpoints progress

```php
// 👉 Your code here
```

## Key Takeaways

✅ Use resolver pattern for queue-safe graphs  
✅ Checkpoint after each step for resumability  
✅ Catch errors and provide fallbacks  
✅ Log everything for observability  
✅ Cache results when appropriate  
✅ Monitor performance metrics  

## Deployment Checklist

- [ ] All chains wrapped with LoggingChain
- [ ] Graphs use CheckpointedGraph
- [ ] Error handling with try-catch
- [ ] Metrics collection enabled
- [ ] Rate limiting configured
- [ ] Caching strategy decided
- [ ] Queue workers tested
- [ ] Database connections pooled
- [ ] API keys secured in .env
- [ ] Monitoring alerts set up

## Next Steps

You've completed all tutorials! 🎉

**You now know:**
✅ Build simple chains  
✅ Add memory and conversation  
✅ Compose complex pipelines  
✅ Create state graph workflows  
✅ Implement RAG systems  
✅ Deploy production-ready code  

→ Explore the [API Reference](../08-api-reference.md) for detailed docs  
→ Check [Advanced Patterns](../07-advanced-patterns.md) for more techniques  

---

**Congratulations!** You're ready to build sophisticated AI systems with laravel-ai-workflows! 🚀

