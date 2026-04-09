# Core Concepts

This document explains the foundational concepts in laravel-ai-workflows. Understanding these will help you design better workflows.

## 1. Chains vs. State Graphs

### Chains

**What:** Linear sequences of prompt-agent-prompt-agent operations.

**Use cases:**
- Simple Q&A
- Multi-step transformations (draft → edit → refine)
- Sequential processing

**Example:**
```php
$draft = Chain::make($agent, PromptTemplate::from('Write: {topic}'));
$edit = Chain::make($agent, PromptTemplate::from('Edit: {draft}'));

$result = $draft->then($edit)->run(['topic' => 'PHP']);
```

### State Graphs

**What:** Directed acyclic graphs (DAGs) with named nodes and conditional routing.

**Use cases:**
- Complex workflows with branching
- Conditional logic (if this, then that)
- Parallel processing
- Deterministic state machines

**Example:**
```php
$graph = new StateGraph();

$graph->addNode('validate', fn($s) => /* ... */);
$graph->addNode('process', fn($s) => /* ... */);

$graph->setEntryPoint('validate');
$graph->addEdge('validate', 'process');
$graph->addConditionalEdge('process', fn($s) => 
    $s->isValid ? 'complete' : 'error'
);

$result = $graph->compile()->invoke($initialState);
```

### Decision Tree

```
┌─────────────────────────────────────┐
│ Linear, sequential processing?      │
├─────────────────────────────────────┤
│ YES → Use Chain                     │
│ NO  → Check if conditional routing  │
└─────────────────────────────────────┘
        │
        v
┌─────────────────────────────────────┐
│ Need branching/conditionals?        │
├─────────────────────────────────────┤
│ YES → Use StateGraph                │
│ NO  → Chain is fine                 │
└─────────────────────────────────────┘
```

## 2. Immutable State

State in laravel-ai-workflows is **always immutable**. This is foundational.

### Why Immutable?

1. **Predictability** — No side effects
2. **Debuggability** — Easy to trace state transitions
3. **Concurrency** — Safe for queue workers
4. **Checkpointing** — Can save/restore safely

### The Pattern

Never mutate state directly:

```php
// ❌ WRONG
$state->notes[] = 'new note';  // Mutates!
return $state;

// ✅ CORRECT
return $state->with([
    'notes' => [...$state->notes, 'new note']
]);
```

### The `with()` Method

Every state has a `with()` method that creates a new instance:

```php
$state->with(['key' => 'new value']);
```

It works like this:

```php
// Takes current state
$original = new MyState(name: 'Alice', age: 30);

// Returns new instance with updates merged
$updated = $original->with(['age' => 31]);

// Original unchanged
assert($original->age === 30);
assert($updated->age === 31);
```

## 3. Prompt Templates

### Syntax

Use `{variable}` for interpolation:

```php
$template = PromptTemplate::from(
    'User: {user_name}, Question: {question}, Context: {context}'
);
```

### Interpolation

```php
$result = $template->format([
    'user_name' => 'Alice',
    'question' => 'What is PHP?',
    'context' => 'I am a beginner.'
]);

// Output:
// User: Alice, Question: What is PHP?, Context: I am a beginner.
```

### Partial Templates

You can format a template with some variables and get back a new template:

```php
$template = PromptTemplate::from('From: {from}, To: {to}, Message: {msg}');

$partial = $template->partial(['from' => 'Alice']);
// Still needs: {to}, {msg}

$complete = $partial->format(['to' => 'Bob', 'msg' => 'Hi']);
// From: Alice, To: Bob, Message: Hi
```

## 4. Agents & Providers

### What's an Agent?

An agent is an AI entity that:
- Takes a prompt (string)
- Returns a response (string or stream)
- Optionally tracks tool calls

### Using `laravel/ai`

The package builds on `laravel/ai`, so you get any provider it supports:

```php
use function Laravel\Ai\agent;

$agent = agent(
    instructions: 'You are a helpful assistant.',
    model: 'gpt-4',  // Optional
);
```

### Provider Resolution

The package reads from config:

```php
// In config/ai-chain.php or .env
'ai.default_provider' => 'openai'
```

You can override per chain:

```php
$chain = Chain::make($agent, $prompt)
    ->withProvider('anthropic')
    ->withModel('claude-3-sonnet');
```

## 5. Memory

### What's Memory?

Memory stores conversation history so the AI understands context.

### Types

1. **Conversation Memory** — Full chat history
2. **Summary Memory** — Condensed history (cost-efficient)
3. **Cache Memory** — Backed by Redis or file cache

### Usage

```php
use Nexus\\Workflow\Memory\InMemoryConversation;

$memory = new InMemoryConversation();
$memory->add('user', 'What is RAG?');
$memory->add('assistant', 'RAG is...');

$prompt = PromptTemplate::from(
    'Chat history:\n{history}\n\nNew question: {input}'
);

$chain = Chain::make($agent, $prompt)
    ->withMemory($memory);

$result = $chain->run(['input' => 'Tell me more']);
```

## 6. Retrieval & RAG

### What's RAG?

**R**etrieval-**A**ugmented **G**eneration:
1. Retrieve relevant documents
2. Inject them as context
3. Generate response based on context

### Example

```php
use Nexus\\Workflow\Retrieval\VectorStoreRetriever;

$retriever = new VectorStoreRetriever($vectorStore);

$prompt = PromptTemplate::from(
    'Context:\n{context}\n\nQuestion: {input}'
);

$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever, topK: 5);

// When you call run(), it automatically:
// 1. Retrieves top 5 documents matching {input}
// 2. Formats them as {context}
// 3. Passes to agent
```

## 7. Input & Output Keys

### Key Concept

Chains and graphs pass data via named keys (not positional).

### Example

```php
// This chain:
$chain = Chain::make($agent, PromptTemplate::from('Q: {question}'), 'answer')

// Expects input:
$result = $chain->run(['question' => 'What is PHP?']);

// And produces output:
// $result['answer'] = 'PHP is a language...'

// Or just the string:
echo $result;  // Casts via __toString()
```

### Chaining Keys

When chaining, output keys must match the next chain's template variables:

```php
$chain1 = Chain::make($agent1, PromptTemplate::from('A: {input}'), 'draft');
$chain2 = Chain::make($agent2, PromptTemplate::from('B: {draft}'), 'final');

$chain1->then($chain2)->run(['input' => 'topic']);
// chain1 output ('draft') → chain2 input (requires 'draft') ✓
```

## 8. Streaming

### Full Response

```php
$result = $chain->run(['input' => 'Hello']);
// Waits for full response, then returns
```

### Streaming

```php
foreach ($chain->stream(['input' => 'Hello']) as $token) {
    echo $token;  // Real-time token output
    flush();      // For web output
}
```

## 9. Graph Terminology

### Nodes

Named units of work:

```php
$graph->addNode('step1', fn($state) => /* return updated state */);
```

### Edges

Connections between nodes:

```php
$graph->addEdge('step1', 'step2');  // Always goes to step2
```

### Conditional Edges

Routing based on state:

```php
$graph->addConditionalEdge('step1', function($state) {
    return $state->score > 0.5 ? 'accept' : 'reject';
});
```

### Entry Point

Where execution begins:

```php
$graph->setEntryPoint('start');
```

### END

Special marker for graph termination:

```php
$graph->addEdge('final', StateGraph::END);
```

## 10. Type Safety

### Contracts

The package defines strict contracts via interfaces:

```php
interface Chain {
    public function run(array $inputs): mixed;
    public function stream(array $inputs): Generator;
    public function inputKeys(): array;
    public function outputKey(): string;
}

interface Node {
    public function handle(State $state): State;
    public function name(): string;
}

interface Memory {
    public function add(string $role, string $content): void;
    public function messages(): array;
    public function asString(): string;
}
```

### Custom State Classes

Define your own state with strict typing:

```php
final class ResearchState extends State
{
    public function __construct(
        public string $query = '',
        public array $documents = [],
        public string $summary = '',
    ) {}

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'documents' => $this->documents,
            'summary' => $this->summary,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            query: $data['query'] ?? '',
            documents: $data['documents'] ?? [],
            summary: $data['summary'] ?? '',
        );
    }
}
```

---

**Next:** [Chains Guide](./03-chains-guide.md) →

