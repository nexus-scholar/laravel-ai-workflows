# Advanced Patterns

This guide covers production patterns, queue workers, checkpointing, and error handling.

## Queue-Based Graph Execution

### Problem

Closures and graphs don't serialize well for queue workers. Solution: use resolver keys.

### Pattern: Resolver-Based Dispatch

```php
// In your application service provider
app()->bind('workflows', function () {
    return [
        'slr_workflow' => function () {
            return buildSLRGraph();  // Built fresh by worker
        },
    ];
});

// In your controller/command
$jobData = [
    'workflow_key' => 'slr_workflow',  // Reference, not closure
    'initial_state' => $state->toArray(),
];

dispatch(new ExecuteGraphJob($jobData));

// In ExecuteGraphJob
class ExecuteGraphJob implements ShouldQueue
{
    public function handle()
    {
        $workflows = app('workflows');
        $graphBuilder = $workflows[$this->jobData['workflow_key']];
        $graph = $graphBuilder();  // Resolve from container
        
        $state = State::fromArray($this->jobData['initial_state']);
        $result = $graph->compile()->invoke($state);
        
        // Handle result
        event(new GraphCompleted($result));
    }
}
```

### Pattern: Job State Checkpointing

Save state at each step for resumability:

```php
class CheckpointingGraph
{
    private string $checkpointKey;

    public function __construct(private CompiledGraph $graph, string $id)
    {
        $this->checkpointKey = "graph.checkpoint.$id";
    }

    public function invoke(State $state): State
    {
        $current = $state;
        
        foreach ($this->graph->stream($current) as $nodeName => $nodeState) {
            // Save checkpoint after each node
            cache()->put(
                $this->checkpointKey,
                $nodeState->toArray(),
                ttl: 86400  // 24 hours
            );
            
            $current = $nodeState;
        }
        
        return $current;
    }

    public function resume(): State
    {
        $saved = cache()->get($this->checkpointKey);
        if (!$saved) {
            throw new RuntimeException('No checkpoint found');
        }
        
        return State::fromArray($saved);
    }
}

// Use it
$graph = new CheckpointingGraph($compiled, 'workflow_123');

try {
    $result = $graph->invoke($initialState);
} catch (Exception $e) {
    // Worker crashed, can resume
    logger()->error('Graph failed', ['error' => $e]);
    
    // Later, resume from checkpoint
    $recovered = $graph->resume();
    $result = $graph->invoke($recovered);
}
```

## Error Handling & Resilience

### Try-Catch with Fallback

```php
try {
    $result = $chain->run(['input' => $userInput]);
} catch (RateLimitException $e) {
    logger()->warning('Rate limited, queuing');
    dispatch(new DelayedChainJob($userInput));
    return 'Request queued. You will receive a response soon.';
} catch (Exception $e) {
    logger()->error('Chain failed', ['error' => $e->getMessage()]);
    return 'Sorry, I encountered an error. Please try again.';
}
```

### Error Recovery in Graphs

```php
final class ResilientState extends State
{
    public function __construct(
        public string $task = '',
        public ?string $result = null,
        public array $errors = [],
        public int $retries = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'task' => $this->task,
            'result' => $this->result,
            'errors' => $this->errors,
            'retries' => $this->retries,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            task: $data['task'] ?? '',
            result: $data['result'] ?? null,
            errors: $data['errors'] ?? [],
            retries: $data['retries'] ?? 0,
        );
    }
}

$graph = new StateGraph();

$graph->addNode('execute', fn(ResilientState $s) => {
    try {
        $result = executeTask($s->task);
        return $s->with(['result' => $result]);
    } catch (Exception $e) {
        return $s->with([
            'errors' => [...$s->errors, $e->getMessage()],
            'retries' => $s->retries + 1,
        ]);
    }
});

$graph->addNode('wait_and_retry', fn(ResilientState $s) => {
    sleep(2 ** $s->retries);  // Exponential backoff
    return $s;
});

$graph->addNode('escalate', fn(ResilientState $s) => {
    logger()->critical('Task failed after retries', [
        'task' => $s->task,
        'errors' => $s->errors,
    ]);
    return $s->with(['result' => 'FAILED']);
});

$graph->setEntryPoint('execute');

$graph->addConditionalEdge('execute', fn(ResilientState $s) =>
    $s->result !== null ? 'success' :
    ($s->retries < 3 ? 'wait_and_retry' : 'escalate')
);

$graph->addEdge('wait_and_retry', 'execute');  // Retry loop
$graph->addEdge('success', StateGraph::END);
$graph->addEdge('escalate', StateGraph::END);
```

## Streaming Responses

### Web Response Streaming

```php
// In controller
public function chat(Request $request)
{
    return response()->stream(function () use ($request) {
        foreach ($chain->stream(['input' => $request->input('q')]) as $token) {
            echo $token;
            flush();
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
}
```

### Server-Sent Events (SSE)

```php
// Blade template
<script>
const eventSource = new EventSource('/api/chat?q=hello');

eventSource.onmessage = (event) => {
    document.getElementById('response').innerHTML += event.data;
};

eventSource.onerror = () => {
    eventSource.close();
};
</script>

// Controller
public function streamChat(Request $request)
{
    return response()->stream(function () use ($request) {
        foreach ($chain->stream(['input' => $request->input('q')]) as $token) {
            echo "data: " . json_encode(['text' => $token]) . "\n\n";
            ob_flush();
            flush();
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
}
```

## Observability & Logging

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
        logger()->info("[$this->name] Starting", ['inputs' => $inputs]);
        
        $start = microtime(true);
        try {
            $result = $this->chain->run($inputs);
            $duration = microtime(true) - $start;
            
            logger()->info("[$this->name] Completed", [
                'duration_ms' => round($duration * 1000),
                'output_keys' => array_keys((array)$result),
            ]);
            
            return $result;
        } catch (Exception $e) {
            logger()->error("[$this->name] Failed", [
                'error' => $e->getMessage(),
                'inputs' => $inputs,
            ]);
            throw $e;
        }
    }

    public function stream(array $inputs): \Generator
    {
        logger()->info("[$this->name] Streaming start");
        yield from $this->chain->stream($inputs);
    }

    public function inputKeys(): array
    {
        return $this->chain->inputKeys();
    }

    public function outputKey(): string
    {
        return $this->chain->outputKey();
    }
}

// Use it
$chain = new LoggingChain(
    Chain::make($agent, $prompt),
    name: 'QA Chain'
);
```

### Graph Execution Observability

```php
class TracingGraphDecorator
{
    public function __construct(private CompiledGraph $graph) {}

    public function invoke(State $state): State
    {
        $trace = [];
        
        foreach ($this->graph->stream($state) as $nodeName => $nodeState) {
            $trace[] = [
                'node' => $nodeName,
                'timestamp' => now()->iso8601(),
                'state_keys' => array_keys($nodeState->toArray()),
            ];
            
            logger()->debug("Graph step", ['node' => $nodeName]);
        }
        
        logger()->info("Graph completed", ['trace' => $trace]);
        
        return $nodeState;
    }
}
```

## Monitoring & Metrics

### Chain Performance Metrics

```php
class MetricsMiddleware
{
    public function __construct(private ChainContract $chain) {}

    public function run(array $inputs): mixed
    {
        $start = microtime(true);
        $tokenCount = 0;

        foreach ($this->chain->stream($inputs) as $token) {
            $tokenCount += strlen($token);
        }

        $duration = microtime(true) - $start;

        metrics()->record('chain.duration', $duration);
        metrics()->record('chain.tokens', $tokenCount);
        metrics()->record('chain.throughput', $tokenCount / $duration);

        return $this->chain->run($inputs);
    }
}
```

## Testing Advanced Patterns

### Mocking Graph Nodes

```php
use Pest;

test('graph routes to correct node based on condition', function () {
    $state = new TestState(score: 0.9);
    
    $graph = new StateGraph();
    $graph->addNode('high', fn($s) => $s->with(['result' => 'high']));
    $graph->addNode('low', fn($s) => $s->with(['result' => 'low']));
    
    $graph->setEntryPoint('classify');
    $graph->addNode('classify', fn($s) => $s);
    
    $graph->addConditionalEdge('classify', fn($s) =>
        $s->score > 0.5 ? 'high' : 'low'
    );

    $compiled = $graph->compile();
    $result = $compiled->invoke($state);
    
    expect($result->result)->toBe('high');
});
```

### Mocking API Responses

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(200, [], json_encode(['answer' => 'Mocked response'])),
]);

$chain = Chain::make($agent, $prompt);
// Inject mock client into agent if needed

$result = $chain->run(['input' => 'test']);
expect($result)->toContain('Mocked');
```

## Performance Optimization

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
        $key = $this->cachePrefix . '.' . md5(json_encode($inputs));
        
        return cache()->remember($key, $this->ttl, fn() =>
            $this->chain->run($inputs)
        );
    }

    public function stream(array $inputs): \Generator
    {
        yield from $this->chain->stream($inputs);
    }

    public function inputKeys(): array { return $this->chain->inputKeys(); }
    public function outputKey(): string { return $this->chain->outputKey(); }
}
```

### Batch Processing

```php
class BatchChainProcessor
{
    public function __construct(
        private ChainContract $chain,
        private int $batchSize = 10,
    ) {}

    public function processBatch(array $items): array
    {
        $results = [];
        
        foreach (array_chunk($items, $this->batchSize) as $batch) {
            foreach ($batch as $item) {
                $results[] = $this->chain->run(['input' => $item]);
            }
            
            // Give system a break between batches
            sleep(1);
        }
        
        return $results;
    }
}

// Use it
$processor = new BatchChainProcessor($chain);
$results = $processor->processBatch($milionItems);
```

---

**Next:** [API Reference](./08-api-reference.md) →

