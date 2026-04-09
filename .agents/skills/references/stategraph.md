# StateGraph Workflows

Architectural guide for building complex agentic workflows with state graphs.

## Core Concepts

A **StateGraph** is a directed acyclic graph (DAG) where:
- **Nodes** are processing units that transform state
- **Edges** connect nodes (can be direct or conditional)
- **State** is immutable data passed between nodes
- **Entry Point** defines where execution begins
- **END** is a special marker that terminates execution

## Immutable State Pattern

All state transitions must create new instances. Use `with()` method:

```php
public function handle(State $state): State 
{
    $result = $this->process($state->data);
    return $state->with(['result' => $result, 'status' => 'complete']);
}
```

**Never mutate in place:**
```php
// ❌ Wrong
$state->data = 'modified';
return $state;

// ✅ Correct
return $state->with(['data' => 'modified']);
```

## Node Types

### Regular Nodes
Execute once per graph execution:
```php
$graph->addNode('process', fn(State $s) => 
    $s->with(['result' => process($s->data)])
);
```

### Conditional Routers
Determine next node based on state:
```php
$graph->addConditionalEdge('classify', fn(State $s) =>
    match(true) {
        $s->score > 80 => 'approve',
        $s->score > 50 => 'review',
        default => 'reject',
    }
);
```

### Looping Nodes
Route back to earlier nodes for retries:
```php
$graph->addNode('retry', fn($s) => 
    $s->with(['attempts' => $s->attempts + 1])
);

$graph->addConditionalEdge('execute', fn($s) =>
    $s->attempts < 3 && !$s->success ? 'retry' : 'finish'
);

$graph->addEdge('retry', 'execute');  // Loop back
```

## Building a Graph

1. **Define State** — Extend `Nexus\\Workflow\Graph\State`
2. **Add Nodes** — Use `addNode(name, callable)`
3. **Add Edges** — Direct with `addEdge()`, conditional with `addConditionalEdge()`
4. **Set Entry Point** — `setEntryPoint(name)`
5. **Compile** — `$graph->compile()`
6. **Invoke** — `$compiled->invoke($initialState)`

## State Design

Create a custom state class:

```php
final class WorkflowState extends State
{
    public function __construct(
        public string $input = '',
        public array $data = [],
        public string $status = 'pending',
    ) {}
    
    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'data' => $this->data,
            'status' => $this->status,
        ];
    }
    
    public static function fromArray(array $data): static
    {
        return new self(
            input: $data['input'] ?? '',
            data: $data['data'] ?? [],
            status: $data['status'] ?? 'pending',
        );
    }
}
```

## Execution Modes

### Synchronous
```php
$result = $graph->compile()->invoke($state);
```

### Streaming (with progress)
```php
foreach ($graph->compile()->stream($state) as $nodeName => $nodeState) {
    echo "Completed: $nodeName\n";
}
```

## Advanced Patterns

### Checkpointing
Save state after each step for resumability:
```php
foreach ($graph->stream($state) as $nodeName => $nodeState) {
    cache()->put("checkpoint.$id", $nodeState->toArray(), ttl: 3600);
}
```

### Queue Execution
For production, dispatch graph execution to workers:
```php
dispatch(new ExecuteGraphJob(
    workflowKey: 'slr',  // Resolved from container
    state: $state->toArray()  // Serialized state
));
```

### Error Handling
Include error nodes for graceful failure handling:
```php
$graph->addNode('error_handler', fn($s) => 
    $s->with(['status' => 'error', 'result' => 'failed'])
);

$graph->addConditionalEdge('process', fn($s) =>
    isset($s->error) ? 'error_handler' : 'success'
);
```

## Performance Considerations

- **State Size** — Keep state minimal; avoid large arrays
- **Node Count** — Graphs with 10+ nodes are common; no hard limit
- **Conditional Complexity** — Keep decision functions simple and fast
- **Memory** — For long workflows, use checkpointing to Redis

## Common Patterns

**Approval Workflow:**
```
Validate → Manager Review → Director Review → Executive Review → Approve/Reject
```

**Data Pipeline:**
```
Extract → Clean → Validate → Analyze → Store
```

**Multi-Agent Coordination:**
```
Router → Agent A → Aggregator → Agent B → Final Output
```

See [Advanced Patterns](../../docs/07-advanced-patterns.md) for production deployment patterns.
