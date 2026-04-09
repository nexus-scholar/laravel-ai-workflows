# API Reference

Complete method signatures and contracts for laravel-ai-chain.

## Table of Contents

1. [Chain](#chain)
2. [ChainFactory](#chainfactory)
3. [SequentialChain](#sequentialchain)
4. [StateGraph](#stategraph)
5. [State](#state)
6. [Prompt Template](#prompttemplate)
7. [Memory](#memory)
8. [Retriever](#retriever)
9. [Contracts](#contracts)

---

## Chain

Main interface for prompt-agent workflows.

### Creating a Chain

```php
Chain::make(
    Agent $agent,
    PromptTemplate $promptTemplate,
    string $outputKey = 'output'
): self
```

Create a new chain from an agent and prompt.

**Parameters:**
- `$agent` — The AI agent to invoke
- `$promptTemplate` — Prompt template with variables
- `$outputKey` — Key for the output in the result

**Example:**
```php
$chain = Chain::make(
    agent(),
    PromptTemplate::from('Q: {question}'),
    'answer'
);
```

### Running a Chain

```php
run(array $inputs): mixed
```

Execute the chain synchronously.

**Parameters:**
- `$inputs` — Associative array of template variables

**Returns:** Mixed (string, array, or custom object)

**Example:**
```php
$result = $chain->run(['question' => 'What is PHP?']);
echo $result;  // Uses __toString() if available
```

### Streaming a Chain

```php
stream(array $inputs): Generator<string>
```

Execute the chain and yield tokens as they arrive.

**Parameters:**
- `$inputs` — Associative array of template variables

**Yields:** Tokens as strings

**Example:**
```php
foreach ($chain->stream(['input' => 'Hello']) as $token) {
    echo $token;
    flush();
}
```

### Input/Output Inspection

```php
inputKeys(): array
```

Get required template variables.

**Returns:** Array of string keys

```php
outputKey(): string
```

Get the output key for this chain.

**Returns:** String key name

**Example:**
```php
$keys = $chain->inputKeys();     // ['question', 'context']
$output = $chain->outputKey();   // 'answer'
```

### Chaining

```php
then(ChainContract $chain): SequentialChain
```

Compose this chain with another.

**Parameters:**
- `$chain` — The next chain to execute

**Returns:** SequentialChain

**Example:**
```php
$workflow = $chain1->then($chain2)->then($chain3);
$result = $workflow->run(['input' => 'topic']);
```

### Memory Integration

```php
withMemory(Memory $memory): self
```

Attach conversation memory to this chain.

**Parameters:**
- `$memory` — Memory implementation

**Returns:** New chain instance (cloned)

**Example:**
```php
$chain = Chain::make($agent, $prompt)
    ->withMemory(new CacheConversationMemory('user.123'));
```

### Retrieval Integration

```php
withRetriever(Retriever $retriever, int $topK = 5): self
```

Attach a retriever for RAG.

**Parameters:**
- `$retriever` — Retriever implementation
- `$topK` — Number of documents to retrieve

**Returns:** New chain instance (cloned)

**Example:**
```php
$chain = Chain::make($agent, $prompt)
    ->withRetriever(new VectorStoreRetriever($store), topK: 10);
```

### Provider Override

```php
withProvider(string|array|null $provider): self
```

Override the AI provider for this chain.

**Parameters:**
- `$provider` — Provider name (e.g., 'openai', 'anthropic')

**Returns:** New chain instance (cloned)

```php
withModel(string|null $model): self
```

Override the model for this chain.

**Parameters:**
- `$model` — Model name (e.g., 'gpt-4', 'claude-3-sonnet')

**Returns:** New chain instance (cloned)

---

## ChainFactory

Fluent builder for chains.

### Static Constructor

```php
ChainFactory::chain(
    Agent $agent,
    PromptTemplate $promptTemplate,
    string $outputKey
): self
```

Start building a chain.

**Example:**
```php
$factory = ChainFactory::chain(
    agent(),
    PromptTemplate::from('Q: {input}'),
    'draft'
);
```

### Adding Steps

```php
thenPrompt(
    Agent $agent,
    PromptTemplate $promptTemplate,
    string $outputKey
): self
```

Add another chain step.

**Example:**
```php
$factory = ChainFactory::chain($agent1, $p1, 'draft')
    ->thenPrompt($agent2, $p2, 'edited')
    ->thenPrompt($agent3, $p3, 'final');
```

### Finalizing

```php
build(): Chain
```

Build and return the composed chain.

**Returns:** Chain instance

**Example:**
```php
$chain = ChainFactory::chain($agent, $prompt, 'output')
    ->thenPrompt($agent2, $prompt2, 'final')
    ->build();
```

---

## SequentialChain

A chain made of multiple chains executed in sequence.

### Constructor (typically not called directly)

```php
new SequentialChain(array $chains)
```

**Parameters:**
- `$chains` — Array of Chain instances

### Methods

Implements the same interface as `Chain`:

```php
run(array $inputs): mixed
stream(array $inputs): Generator<string>
inputKeys(): array
outputKey(): string
then(ChainContract $chain): SequentialChain
withMemory(Memory $memory): self
withRetriever(Retriever $retriever, int $topK = 5): self
withProvider(string|array|null $provider): self
withModel(string|null $model): self
```

---

## StateGraph

Builder for directed acyclic graph workflows.

### Constructor

```php
new StateGraph()
```

Create a new graph.

### Adding Nodes

```php
addNode(string $name, Node|callable $node): self
```

Add a named processing node.

**Parameters:**
- `$name` — Node identifier
- `$node` — Either a Node instance or a callable that takes State and returns State

**Throws:** `GraphValidationException` if name is empty or already exists

**Example:**
```php
$graph->addNode('process', fn(MyState $s) =>
    $s->with(['status' => 'processing'])
);
```

### Adding Edges

```php
addEdge(string $from, string $to): self
```

Add a direct edge between nodes.

**Parameters:**
- `$from` — Source node
- `$to` — Destination node (or StateGraph::END)

**Throws:** `GraphValidationException` if nodes are empty

```php
addConditionalEdge(string $from, callable $condition): self
```

Add a conditional edge with routing logic.

**Parameters:**
- `$from` — Source node
- `$condition` — Callable that receives State and returns destination node name

**Example:**
```php
$graph->addConditionalEdge('classify', fn(MyState $s) =>
    $s->score > 0.5 ? 'accept' : 'reject'
);
```

### Entry Point

```php
setEntryPoint(string $nodeName): self
```

Set where graph execution begins.

**Parameters:**
- `$nodeName` — Name of starting node

**Throws:** `GraphValidationException` if name is empty

**Example:**
```php
$graph->setEntryPoint('start');
```

### Compilation

```php
compile(): CompiledGraph
```

Validate and compile the graph into an executable form.

**Returns:** CompiledGraph instance

**Throws:** `GraphValidationException` if:
- Graph is empty
- Entry point not set
- Entry point not registered

**Example:**
```php
$compiled = $graph->compile();
$result = $compiled->invoke($initialState);
```

---

## State

Base class for all graph states. Must be immutable.

### Abstract Methods

```php
abstract public function toArray(): array
```

Convert state to associative array.

**Returns:** Array representation

```php
abstract public static function fromArray(array $data): static
```

Create state from array.

**Parameters:**
- `$data` — Array of state values

**Returns:** New state instance

### Concrete Method

```php
public function with(array $updates): static
```

Return new state with updated values.

**Parameters:**
- `$updates` — Associative array of changes

**Returns:** New state instance (original unchanged)

**Example:**
```php
$state = new MyState(count: 0);
$updated = $state->with(['count' => 1]);

// Original unchanged
assert($state->count === 0);
assert($updated->count === 1);
```

### Implementing a Custom State

```php
final class DocumentState extends State
{
    public function __construct(
        public string $text = '',
        public array $entities = [],
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'entities' => $this->entities,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            text: $data['text'] ?? '',
            entities: $data['entities'] ?? [],
        );
    }
}
```

---

## PromptTemplate

Templates with variable interpolation.

### Creating Templates

```php
PromptTemplate::from(string $template): self
```

Create a template from a string.

**Parameters:**
- `$template` — String with `{variable}` placeholders

**Example:**
```php
$template = PromptTemplate::from(
    'Question: {question}\nContext: {context}'
);
```

### Interpolation

```php
format(array $variables): string
```

Interpolate variables into the template.

**Parameters:**
- `$variables` — Associative array of values

**Returns:** Formatted string

**Example:**
```php
$prompt = $template->format([
    'question' => 'What is PHP?',
    'context' => 'I am a beginner.'
]);
```

### Partial Application

```php
partial(array $variables): self
```

Pre-fill some variables, return new template for remaining ones.

**Parameters:**
- `$variables` — Variables to pre-fill

**Returns:** New template

**Example:**
```php
$template = PromptTemplate::from('{role}: {name}');
$partial = $template->partial(['role' => 'Admin']);

// Now only needs {name}
echo $partial->format(['name' => 'Alice']);
```

---

## Memory

Interface for conversation storage.

### Adding Messages

```php
add(string $role, string $content): void
```

Add a message to memory.

**Parameters:**
- `$role` — 'user' or 'assistant'
- `$content` — Message text

**Example:**
```php
$memory->add('user', 'What is RAG?');
$memory->add('assistant', 'RAG combines retrieval with generation...');
```

### Retrieving Messages

```php
messages(): array
```

Get all stored messages.

**Returns:** Array of `['role' => string, 'content' => string]`

### Clearing Memory

```php
clear(): void
```

Remove all messages.

### Formatting for Prompts

```php
asString(): string
```

Get memory as formatted string for prompt injection.

**Returns:** String suitable for `{history}` placeholder

### Implementations

#### InMemoryConversation

```php
new InMemoryConversation()
```

Stores in PHP memory (non-persistent).

#### CacheConversationMemory

```php
new CacheConversationMemory(
    key: string,
    ttl: int = 3600
)
```

Stores in Laravel cache (persistent).

**Parameters:**
- `$key` — Cache key
- `$ttl` — Time-to-live in seconds

#### SummaryMemory

```php
new SummaryMemory(
    maxMessages: int = 10,
    agent: Agent,
    summarizerPrompt: string = '...'
)
```

Summarizes old messages to save tokens.

**Parameters:**
- `$maxMessages` — Keep this many recent messages
- `$agent` — Agent for summarization
- `$summarizerPrompt` — Custom summary prompt

---

## Retriever

Interface for document retrieval.

### Main Method

```php
retrieve(string $query, int $topK = 5): array
```

Retrieve documents matching query.

**Parameters:**
- `$query` — Search query
- `$topK` — Number of results

**Returns:** Array of Document objects

### Implementations

#### VectorStoreRetriever

```php
new VectorStoreRetriever(
    VectorStore $vectorStore,
    int $topK = 5
)
```

Semantic search using embeddings.

#### HybridRetriever

```php
new HybridRetriever(
    Retriever $vectorRetriever,
    Retriever $lexicalRetriever,
    float $alpha = 0.5
)
```

Combines vector and lexical search.

#### RerankingRetriever

```php
new RerankingRetriever(
    Retriever $baseRetriever,
    string $rerankModel,
    int $topK = 5
)
```

Re-scores results for better relevance.

### Document Object

```php
class Document
{
    public string $id;
    public string $text;
    public array $metadata;
    public float $score;
}
```

---

## Contracts

Core interfaces you implement for custom types.

### Chain Contract

```php
interface Chain
{
    public function run(array $inputs): mixed;
    public function stream(array $inputs): Generator;
    public function inputKeys(): array;
    public function outputKey(): string;
}
```

### Node Contract

```php
interface Node
{
    public function handle(State $state): State;
    public function name(): string;
}
```

### Memory Contract

```php
interface Memory
{
    public function add(string $role, string $content): void;
    public function messages(): array;
    public function clear(): void;
    public function asString(): string;
}
```

### Retriever Contract

```php
interface Retriever
{
    public function retrieve(string $query, int $topK = 5): array;
}
```

---

## CompiledGraph

Result of compiling a StateGraph.

### Invoking

```php
invoke(State $state): State
```

Execute graph and return final state.

**Parameters:**
- `$state` — Initial state

**Returns:** Final state after all nodes

### Streaming

```php
stream(State $state): Generator<string, State>
```

Execute graph and yield after each node.

**Parameters:**
- `$state` — Initial state

**Yields:** Node name → Updated state

---

## Exception Hierarchy

```
Exception
├── GraphValidationException
│   └── Thrown when graph is invalid
├── RateLimitException
│   └── Provider rate limit hit
└── ChainExecutionException
    └── Chain execution failed
```

---

**Documentation complete!** Refer back to specific guides for examples.

← [Back to Advanced Patterns](./07-advanced-patterns.md)

