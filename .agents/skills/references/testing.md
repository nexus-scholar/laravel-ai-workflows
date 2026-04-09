# Testing Chains & Workflows

Guide for testing AI workflows with Laravel and Pest.

## Testing Setup

All examples use Pest 3. Install in the package:

```bash
composer require --dev pestphp/pest
```

## Basic Chain Testing

### Mock Agent

Create a predictable fake agent:

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;

class DemoAgent
{
    public function __construct(private array $responses = []) {}
    
    public function prompt(string $prompt): string
    {
        return array_shift($this->responses) ?? 'Default response';
    }
}

it('runs a simple chain', function () {
    $agent = new DemoAgent(['This is a test response']);
    
    $chain = Chain::make(
        $agent,
        PromptTemplate::from('Q: {input}'),
        'answer'
    );
    
    $result = $chain->run(['input' => 'Test question']);
    
    expect($result)->toBe('This is a test response');
});
```

### Test with Multiple Responses

```php
it('handles multi-turn chains', function () {
    $agent = new DemoAgent([
        'First response',
        'Second response',
        'Third response',
    ]);
    
    $chain1 = Chain::make($agent, PromptTemplate::from('A: {input}'), 'step1');
    $chain2 = Chain::make($agent, PromptTemplate::from('B: {step1}'), 'step2');
    
    $result = $chain1->then($chain2)->run(['input' => 'test']);
    
    expect($result['step1'])->toBe('First response');
    expect($result['step2'])->toBe('Second response');
});
```

## State Graph Testing

### Simple State Test

```php
use Nexus\\Workflow\Graph\State;
use Nexus\\Workflow\Graph\StateGraph;

final class TestState extends State
{
    public function __construct(
        public int $count = 0,
        public string $status = 'pending'
    ) {}
    
    public function toArray(): array
    {
        return ['count' => $this->count, 'status' => $this->status];
    }
    
    public static function fromArray(array $data): static
    {
        return new self(
            count: $data['count'] ?? 0,
            status: $data['status'] ?? 'pending'
        );
    }
}

it('increments state in graph', function () {
    $graph = new StateGraph();
    
    $graph->addNode('increment', fn($s) => 
        $s->with(['count' => $s->count + 1])
    );
    
    $graph->setEntryPoint('increment');
    $graph->addEdge('increment', StateGraph::END);
    
    $result = $graph->compile()->invoke(new TestState(count: 5));
    
    expect($result->count)->toBe(6);
});
```

### Conditional Routing Test

```php
it('routes based on state', function () {
    $graph = new StateGraph();
    
    $graph->addNode('check', fn($s) => $s);
    $graph->addNode('success', fn($s) => $s->with(['status' => 'success']));
    $graph->addNode('failure', fn($s) => $s->with(['status' => 'failure']));
    
    $graph->setEntryPoint('check');
    $graph->addConditionalEdge('check', fn($s) => 
        $s->count > 10 ? 'success' : 'failure'
    );
    $graph->addEdge('success', StateGraph::END);
    $graph->addEdge('failure', StateGraph::END);
    
    $result1 = $graph->compile()->invoke(new TestState(count: 15));
    expect($result1->status)->toBe('success');
    
    $result2 = $graph->compile()->invoke(new TestState(count: 5));
    expect($result2->status)->toBe('failure');
});
```

## Memory Testing

### InMemory Test

```php
use Nexus\\Workflow\Memory\InMemoryConversation;

it('stores conversation', function () {
    $memory = new InMemoryConversation();
    
    $memory->add('user', 'Hello');
    $memory->add('assistant', 'Hi there!');
    
    $messages = $memory->messages();
    
    expect($messages)->toHaveCount(2);
    expect($messages[0])->toEqual(['role' => 'user', 'content' => 'Hello']);
});
```

### Cache Memory Test

```php
use Nexus\\Workflow\Memory\CacheConversationMemory;

it('persists to cache', function () {
    $memory1 = new CacheConversationMemory(key: 'test-conv', ttl: 3600);
    $memory1->add('user', 'First message');
    
    // Simulate new request
    $memory2 = new CacheConversationMemory(key: 'test-conv', ttl: 3600);
    $messages = $memory2->messages();
    
    expect($messages)->toHaveCount(1);
    expect($messages[0]['content'])->toBe('First message');
    
    $memory2->clear();
});
```

## Mock Retriever Testing

```php
use Nexus\\Workflow\Retrieval\Document;
use Nexus\\Workflow\Contracts\Retriever;

class MockRetriever implements Retriever
{
    public function __construct(private array $documents = []) {}
    
    public function retrieve(string $query, int $topK = 5): array
    {
        return array_slice($this->documents, 0, $topK);
    }
}

it('retrieves documents', function () {
    $docs = [
        new Document(id: '1', text: 'First document', metadata: []),
        new Document(id: '2', text: 'Second document', metadata: []),
    ];
    
    $retriever = new MockRetriever($docs);
    $results = $retriever->retrieve('test', topK: 1);
    
    expect($results)->toHaveCount(1);
    expect($results[0]->text)->toBe('First document');
});
```

## Integration Testing

### Full Chain with Mocks

```php
it('runs full chain with mocks', function () {
    $agent = new DemoAgent(['RAG is retrieval-augmented generation']);
    $retriever = new MockRetriever([
        new Document(id: '1', text: 'Document about RAG', metadata: []),
    ]);
    
    $prompt = PromptTemplate::from(
        'Context: {context}\n\nQ: {input}'
    );
    
    $chain = Chain::make($agent, $prompt, 'answer')
        ->withRetriever($retriever);
    
    $result = $chain->run(['input' => 'What is RAG?']);
    
    expect($result)->toContain('RAG');
});
```

### Full Workflow Test

```php
it('completes full workflow', function () {
    // 1. Create state
    $state = new TestState(count: 0, status: 'pending');
    
    // 2. Build graph
    $graph = new StateGraph();
    $graph->addNode('step1', fn($s) => $s->with(['count' => $s->count + 1]));
    $graph->addNode('step2', fn($s) => $s->with(['count' => $s->count * 2]));
    $graph->addNode('finish', fn($s) => $s->with(['status' => 'complete']));
    
    $graph->setEntryPoint('step1');
    $graph->addEdge('step1', 'step2');
    $graph->addEdge('step2', 'finish');
    $graph->addEdge('finish', StateGraph::END);
    
    // 3. Execute
    $result = $graph->compile()->invoke($state);
    
    // 4. Assert
    expect($result->count)->toBe(2);      // (0+1)*2
    expect($result->status)->toBe('complete');
});
```

## Best Practices

1. **Mock External Services** — Don't call real APIs in tests
2. **Isolate Chains** — Test chains independently first
3. **Test State Changes** — Verify immutability and updates
4. **Test Routing** — Cover all conditional paths
5. **Test Edge Cases** — Empty inputs, null values, etc
6. **Use Descriptive Names** — Clear test names
7. **Clean Up** — Clear cache/memory after tests
8. **Snapshot Tests** — For AI output, test structure not exact text

## Running Tests

```bash
# All tests
vendor/bin/pest

# Specific file
vendor/bin/pest tests/Feature/ChainTest.php

# With coverage
vendor/bin/pest --coverage

# Watch mode
vendor/bin/pest --watch
```

See [tests/](../../tests/) for more examples.

