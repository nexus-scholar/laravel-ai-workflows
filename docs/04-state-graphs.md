# State Graphs

State Graphs enable complex, branching workflows with conditional routing. Use them when you need more control than linear chains provide.

## When to Use State Graphs

| Use Case | Chain | StateGraph |
|----------|-------|-----------|
| Linear pipeline | ✅ | ❌ |
| Conditional branching | ❌ | ✅ |
| Parallel workflows | ❌ | ✅ |
| Loops/retries | ❌ | ✅ |
| State-dependent routing | ❌ | ✅ |
| Multi-agent coordination | Maybe | ✅ |

## Core Concepts

### State

Immutable data structure passed through the graph:

```php
use Nexus\\Workflow\Graph\State;

final class DocumentState extends State
{
    public function __construct(
        public string $text = '',
        public array $entities = [],
        public string $category = '',
        public float $confidence = 0.0,
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'entities' => $this->entities,
            'category' => $this->category,
            'confidence' => $this->confidence,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            text: $data['text'] ?? '',
            entities: $data['entities'] ?? [],
            category: $data['category'] ?? '',
            confidence: $data['confidence'] ?? 0.0,
        );
    }
}
```

### Nodes

Named processing units that transform state:

```php
$graph->addNode('extract', fn(DocumentState $s) => 
    $s->with([
        'entities' => extractEntities($s->text)
    ])
);

$graph->addNode('categorize', fn(DocumentState $s) =>
    $s->with([
        'category' => categorizeText($s->text),
        'confidence' => 0.92
    ])
);
```

### Edges

Connections between nodes:

```php
// Direct edge (always goes to next node)
$graph->addEdge('extract', 'categorize');

// Conditional edge (routing based on state)
$graph->addConditionalEdge('categorize', fn(DocumentState $s) =>
    $s->confidence > 0.8 ? 'save' : 'review'
);

// End node (terminates graph)
$graph->addEdge('save', StateGraph::END);
```

## Building a Graph

### Step 1: Define State

```php
final class WorkflowState extends State
{
    public function __construct(
        public string $input = '',
        public array $data = [],
        public string $status = 'pending',
        public string $result = '',
    ) {}

    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'data' => $this->data,
            'status' => $this->status,
            'result' => $this->result,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            input: $data['input'] ?? '',
            data: $data['data'] ?? [],
            status: $data['status'] ?? 'pending',
            result: $data['result'] ?? '',
        );
    }
}
```

### Step 2: Create Graph

```php
use Nexus\\Workflow\Graph\StateGraph;

$graph = new StateGraph();
```

### Step 3: Add Nodes

```php
$graph->addNode('validate', fn(WorkflowState $s) => {
    $valid = !empty($s->input);
    return $s->with([
        'status' => $valid ? 'valid' : 'error',
        'data' => [...$s->data, 'validation_passed' => $valid]
    ]);
});

$graph->addNode('process', fn(WorkflowState $s) => {
    $result = processData($s->input);
    return $s->with([
        'status' => 'processed',
        'result' => $result,
        'data' => [...$s->data, 'processing_time' => microtime(true)]
    ]);
});

$graph->addNode('save', fn(WorkflowState $s) => {
    saveToDatabase($s->result);
    return $s->with(['status' => 'saved']);
});

$graph->addNode('error', fn(WorkflowState $s) =>
    $s->with(['status' => 'failed'])
);
```

### Step 4: Set Entry Point

```php
$graph->setEntryPoint('validate');
```

### Step 5: Add Edges

```php
// Direct edges
$graph->addEdge('validate', 'process');
$graph->addEdge('process', 'save');

// Conditional edges
$graph->addConditionalEdge('validate', fn(WorkflowState $s) =>
    $s->status === 'valid' ? 'process' : 'error'
);

// End
$graph->addEdge('save', StateGraph::END);
$graph->addEdge('error', StateGraph::END);
```

### Step 6: Compile and Execute

```php
$compiled = $graph->compile();

$initialState = new WorkflowState(input: 'data to process');
$finalState = $compiled->invoke($initialState);

echo $finalState->result;   // 'processed data'
echo $finalState->status;   // 'saved'
```

## Routing & Conditional Edges

### Simple Routing

Route to one of two nodes based on state:

```php
$graph->addConditionalEdge('classify', fn(MyState $s) =>
    $s->score > 0.5 ? 'accept' : 'reject'
);

$graph->addNode('accept', fn(MyState $s) => $s->with(['action' => 'approved']));
$graph->addNode('reject', fn(MyState $s) => $s->with(['action' => 'denied']));
```

### Complex Routing

Route to multiple destinations:

```php
$graph->addConditionalEdge('router', fn(MyState $s) => 
    match(true) {
        $s->type === 'urgent' => 'high_priority',
        $s->type === 'normal' => 'standard',
        $s->type === 'low' => 'backlog',
        default => 'error',
    }
);
```

### Looping

Route back to an earlier node:

```php
$graph->addNode('retry', fn(MyState $s) => 
    $s->with(['attempts' => $s->attempts + 1])
);

// Conditional edge that loops
$graph->addConditionalEdge('process', fn(MyState $s) =>
    $s->attempts < 3 && !$s->success ? 'retry' : 'complete'
);

// Retry loops back to process
$graph->addEdge('retry', 'process');
```

## Real-World Examples

### Example 1: Data Validation Pipeline

```php
final class ValidationState extends State
{
    public function __construct(
        public array $record = [],
        public array $errors = [],
        public bool $valid = false,
    ) {}

    public function toArray(): array
    {
        return [
            'record' => $this->record,
            'errors' => $this->errors,
            'valid' => $this->valid,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            record: $data['record'] ?? [],
            errors: $data['errors'] ?? [],
            valid: $data['valid'] ?? false,
        );
    }
}

$graph = new StateGraph();

$graph->addNode('schema_check', fn(ValidationState $s) => {
    $errors = [];
    if (empty($s->record['email'])) $errors[] = 'Email required';
    if (empty($s->record['name'])) $errors[] = 'Name required';
    
    return $s->with(['errors' => $errors]);
});

$graph->addNode('type_check', fn(ValidationState $s) => {
    if (!filter_var($s->record['email'], FILTER_VALIDATE_EMAIL)) {
        $s->errors[] = 'Invalid email format';
    }
    return $s->with(['errors' => $s->errors]);
});

$graph->addNode('sanitize', fn(ValidationState $s) => {
    $clean = [
        'email' => filter_var($s->record['email'], FILTER_SANITIZE_EMAIL),
        'name' => htmlspecialchars($s->record['name']),
    ];
    return $s->with(['record' => $clean, 'valid' => true]);
});

$graph->addNode('reject', fn(ValidationState $s) =>
    $s->with(['valid' => false])
);

$graph->setEntryPoint('schema_check');
$graph->addEdge('schema_check', 'type_check');

$graph->addConditionalEdge('type_check', fn(ValidationState $s) =>
    count($s->errors) === 0 ? 'sanitize' : 'reject'
);

$graph->addEdge('sanitize', StateGraph::END);
$graph->addEdge('reject', StateGraph::END);

$compiled = $graph->compile();
$result = $compiled->invoke(new ValidationState(
    record: ['email' => 'user@example.com', 'name' => 'Alice']
));

assert($result->valid === true);
```

### Example 2: Multi-Step Approval Workflow

```php
final class ApprovalState extends State
{
    public function __construct(
        public string $request = '',
        public float $amount = 0.0,
        public array $approvers = [],
        public int $approvalLevel = 0,
        public string $status = 'pending',
    ) {}

    public function toArray(): array
    {
        return [
            'request' => $this->request,
            'amount' => $this->amount,
            'approvers' => $this->approvers,
            'approvalLevel' => $this->approvalLevel,
            'status' => $this->status,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            request: $data['request'] ?? '',
            amount: $data['amount'] ?? 0.0,
            approvers: $data['approvers'] ?? [],
            approvalLevel: $data['approvalLevel'] ?? 0,
            status: $data['status'] ?? 'pending',
        );
    }
}

$graph = new StateGraph();

$graph->addNode('level1_review', fn(ApprovalState $s) => {
    // Manager approval
    $approved = $s->amount < 1000;
    return $s->with([
        'approvalLevel' => 1,
        'status' => $approved ? 'level1_approved' : 'rejected',
    ]);
});

$graph->addNode('level2_review', fn(ApprovalState $s) => {
    // Director approval
    $approved = $s->amount < 10000;
    return $s->with([
        'approvalLevel' => 2,
        'status' => $approved ? 'level2_approved' : 'rejected',
    ]);
});

$graph->addNode('exec_review', fn(ApprovalState $s) => {
    // Executive approval
    return $s->with([
        'approvalLevel' => 3,
        'status' => 'approved',
    ]);
});

$graph->addNode('process', fn(ApprovalState $s) =>
    $s->with(['status' => 'processed'])
);

$graph->addNode('reject', fn(ApprovalState $s) =>
    $s->with(['status' => 'rejected'])
);

$graph->setEntryPoint('level1_review');

$graph->addConditionalEdge('level1_review', fn(ApprovalState $s) =>
    $s->status === 'rejected' ? 'reject' : 'level2_review'
);

$graph->addConditionalEdge('level2_review', fn(ApprovalState $s) =>
    match($s->status) {
        'rejected' => 'reject',
        'level2_approved' => $s->amount > 5000 ? 'exec_review' : 'process',
        default => 'process',
    }
);

$graph->addEdge('exec_review', 'process');
$graph->addEdge('process', StateGraph::END);
$graph->addEdge('reject', StateGraph::END);

$compiled = $graph->compile();
$result = $compiled->invoke(new ApprovalState(
    request: 'Server upgrade',
    amount: 7500.0
));

echo $result->status;  // 'processed' (after exec review)
```

### Example 3: Agentic Loop (Retry with Backoff)

```php
final class AgentState extends State
{
    public function __construct(
        public string $task = '',
        public ?string $result = null,
        public int $attempts = 0,
        public array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'task' => $this->task,
            'result' => $this->result,
            'attempts' => $this->attempts,
            'errors' => $this->errors,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            task: $data['task'] ?? '',
            result: $data['result'] ?? null,
            attempts: $data['attempts'] ?? 0,
            errors: $data['errors'] ?? [],
        );
    }
}

$graph = new StateGraph();

$graph->addNode('execute', fn(AgentState $s) => {
    try {
        $result = performTask($s->task);
        return $s->with(['result' => $result, 'attempts' => $s->attempts + 1]);
    } catch (Exception $e) {
        return $s->with([
            'errors' => [...$s->errors, $e->getMessage()],
            'attempts' => $s->attempts + 1,
        ]);
    }
});

$graph->addNode('wait', fn(AgentState $s) => {
    sleep(2 ** $s->attempts);  // Exponential backoff
    return $s;
});

$graph->setEntryPoint('execute');

$graph->addConditionalEdge('execute', fn(AgentState $s) =>
    $s->result !== null ? 'success' : 
    ($s->attempts < 3 ? 'wait' : 'failure')
);

$graph->addNode('success', fn(AgentState $s) =>
    $s->with(['result' => $s->result])
);

$graph->addNode('failure', fn(AgentState $s) =>
    $s->with(['result' => 'Task failed after retries'])
);

$graph->addEdge('wait', 'execute');  // Loop back
$graph->addEdge('success', StateGraph::END);
$graph->addEdge('failure', StateGraph::END);

$compiled = $graph->compile();
$result = $compiled->invoke(new AgentState(task: 'fetch_data'));
```

## Streaming Graph Execution

Stream updates as nodes complete:

```php
$compiled = $graph->compile();

foreach ($compiled->stream($initialState) as $nodeName => $state) {
    echo "Completed: $nodeName\n";
    echo "Status: " . $state->status . "\n\n";
}
```

## Error Handling in Graphs

### Error Nodes

```php
$graph->addNode('error_handler', fn(MyState $s) => {
    logger()->error('Workflow error', ['state' => $s->toArray()]);
    return $s->with(['status' => 'error']);
});

$graph->addConditionalEdge('some_node', fn(MyState $s) =>
    isset($s->error) ? 'error_handler' : 'next_node'
);
```

---

**Next:** [Memory Systems](./05-memory-systems.md) →

