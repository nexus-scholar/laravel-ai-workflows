---
name: laravel-ai-workflows
description: Composition framework for building AI workflows with laravel/ai. Chains, state graphs, memory, and RAG systems with type-safe immutable state.
---

# Laravel AI Workflows — Agent Skills & Task Reference

This file helps AI agents understand the package structure and discover common tasks.

## Quick Links

**📖 Documentation:** [Complete docs/](../../docs/)  
**🎓 Tutorials:** [6 progressive tutorials](../../docs/tutorials/)  
**💻 Examples:** [Runnable examples](../../examples/)  
**📖 README:** [GitHub README](../../README.md)

---

## Core Concepts

- **Chains** — Linear prompt → agent → output flows with composition
- **State Graphs** — Deterministic DAGs with nodes, edges, conditional routing
- **Memory** — Persistent conversation context (in-memory, cache, summary)
- **Retriever** — Document search & injection for RAG (vector, hybrid, reranked)
- **State** — Type-safe, immutable workflow data

---

## Common Tasks

### 1. Build a Simple Chain

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

$chain = Chain::make(
    agent(instructions: 'You are helpful'),
    PromptTemplate::from('Q: {input}'),
    outputKey: 'answer'
);

$result = $chain->run(['input' => 'What is RAG?']);
```

**Learn More:** [Getting Started](../../docs/01-getting-started.md), [Tutorial 1](../../docs/tutorials/01-beginner-first-chain.md)

---

### 2. Create a Multi-Stage Pipeline

```php
use Nexus\\Workflow\Chains\ChainFactory;

$pipeline = ChainFactory::chain($agent1, $p1, 'draft')
    ->thenPrompt($agent2, $p2, 'edited')
    ->thenPrompt($agent3, $p3, 'final')
    ->build();

$result = $pipeline->run(['topic' => 'AI']);
```

**Learn More:** [Chains Guide](../../docs/03-chains-guide.md), [Tutorial 3](../../docs/tutorials/03-chains-composition.md)

---

### 3. Add Conversation Memory

```php
use Nexus\\Workflow\Memory\CacheConversationMemory;

$memory = new CacheConversationMemory(key: "chat.$userId", ttl: 86400);

$chain = Chain::make($agent, $prompt)->withMemory($memory);

// Multi-turn: AI remembers context
$chain->run(['input' => 'What is PHP?']);
$chain->run(['input' => 'How do I use it?']);  // Remembers!
```

**Learn More:** [Memory Systems](../../docs/05-memory-systems.md), [Tutorial 2](../../docs/tutorials/02-memory-and-conversation.md)

---

### 4. Build a State Graph Workflow

```php
use Nexus\\Workflow\Graph\State;
use Nexus\\Workflow\Graph\StateGraph;

final class ProcessingState extends State
{
    public function __construct(
        public string $data = '',
        public int $score = 0,
        public string $result = '',
    ) {}
    
    public function toArray(): array
    {
        return ['data' => $this->data, 'score' => $this->score, 'result' => $this->result];
    }
    
    public static function fromArray(array $data): static
    {
        return new self(
            data: $data['data'] ?? '',
            score: $data['score'] ?? 0,
            result: $data['result'] ?? '',
        );
    }
}

$graph = new StateGraph();

$graph->addNode('assess', fn($s) => $s->with(['score' => rate($s->data)]));
$graph->addNode('approve', fn($s) => $s->with(['result' => 'APPROVED']));
$graph->addNode('reject', fn($s) => $s->with(['result' => 'REJECTED']));

$graph->setEntryPoint('assess');
$graph->addConditionalEdge('assess', fn($s) => $s->score > 80 ? 'approve' : 'reject');

$result = $graph->compile()->invoke(new ProcessingState(data: 'sample'));
```

**Learn More:** [State Graphs](../../docs/04-state-graphs.md), [Tutorial 4](../../docs/tutorials/04-state-graphs-workflows.md)

---

### 5. Implement RAG (Retrieval-Augmented Generation)

```php
use Nexus\\Workflow\Retrieval\VectorStoreRetriever;

$chain = Chain::make(
    agent(),
    PromptTemplate::from("Context:\n{context}\n\nQ: {input}")
)->withRetriever(new VectorStoreRetriever(app('vector-store'), topK: 5));

// Automatically retrieves, injects, generates answer
$answer = $chain->run(['input' => 'How do I deploy?']);
```

**Learn More:** [Retrieval & RAG](../../docs/06-retrieval-rag.md), [Tutorial 5](../../docs/tutorials/05-advanced-rag.md)

---

### 6. Implement Production Patterns

- **Queue-Safe Execution** — Resolver pattern for workers
- **Checkpointing** — Save/resume workflows
- **Error Handling** — Resilience with retries
- **Monitoring** — Logging & observability

**Learn More:** [Advanced Patterns](../../docs/07-advanced-patterns.md), [Tutorial 6](../../docs/tutorials/06-production-patterns.md)

---

## Skill: Add a Custom State

**When:** You need a workflow with typed state variables.

**Steps:**
1. Extend `Nexus\\Workflow\Graph\State`
2. Implement `toArray()` and `fromArray(array $data)`
3. Use `$state->with([...])` for immutable updates
4. **Example:** See [Tutorial 4](../../docs/tutorials/04-state-graphs-workflows.md)

---

## Skill: Add a StateGraph Node

**When:** You need to add a processing step to a workflow.

**Steps:**
1. Create a node as a callable: `fn(State $s) => $s->with([...updates...])`
2. Or implement `Nexus\\Workflow\Contracts\Node` interface
3. Add to graph: `$graph->addNode('name', $nodeCallable)`
4. **Example:** See [State Graphs](../../docs/04-state-graphs.md)

---

## Skill: Add Conditional Routing

**When:** Workflow needs to branch based on state.

**Steps:**
1. Use `$graph->addConditionalEdge($from, $decisionFn)`
2. The callable receives State and returns next node name or `StateGraph::END`
3. **Example:** See [State Graphs - Routing](../../docs/04-state-graphs.md#routing--conditional-edges)

---

## Skill: Add a Custom Retriever

**When:** Need to search a new data source.

**Steps:**
1. Implement `Nexus\\Workflow\Contracts\Retriever`
2. Implement `retrieve(string $query, int $topK = 5): array`
3. Return array of `Nexus\\Workflow\Retrieval\Document`
4. **Example:** See [Retrieval & RAG](../../docs/06-retrieval-rag.md)

---

## File Structure

```
laravel-ai-workflows/
├── src/
│   ├── Chains/
│   ├── Graph/
│   ├── Memory/
│   ├── Retrieval/
│   ├── Prompts/
│   └── Contracts/
├── docs/              ← Complete documentation
│   ├── 01-getting-started.md
│   ├── 02-core-concepts.md
│   ├── 03-chains-guide.md
│   ├── 04-state-graphs.md
│   ├── 05-memory-systems.md
│   ├── 06-retrieval-rag.md
│   ├── 07-advanced-patterns.md
│   ├── 08-api-reference.md
│   └── tutorials/     ← 6 progressive tutorials
├── examples/          ← Runnable examples
├── README.md
└── .agents/skills/    ← This file
```

---

## Documentation Reference

| Document | Purpose | Read Time |
|----------|---------|-----------|
| [README](../../README.md) | Overview & quick examples | 5 min |
| [Getting Started](../../docs/01-getting-started.md) | Installation & first steps | 20 min |
| [Core Concepts](../../docs/02-core-concepts.md) | Foundational concepts | 15 min |
| [Chains Guide](../../docs/03-chains-guide.md) | Building & composing | 25 min |
| [State Graphs](../../docs/04-state-graphs.md) | Workflows & routing | 30 min |
| [Memory Systems](../../docs/05-memory-systems.md) | Persistence | 20 min |
| [Retrieval & RAG](../../docs/06-retrieval-rag.md) | Vector search & RAG | 30 min |
| [Advanced Patterns](../../docs/07-advanced-patterns.md) | Production patterns | 35 min |
| [API Reference](../../docs/08-api-reference.md) | Complete API | 30 min |

---

## Tutorials

Learn through hands-on examples:

1. [Your First Chain](../../docs/tutorials/01-beginner-first-chain.md) (15 min)
2. [Memory & Conversation](../../docs/tutorials/02-memory-and-conversation.md) (20 min)
3. [Chains Composition](../../docs/tutorials/03-chains-composition.md) (25 min)
4. [State Graphs](../../docs/tutorials/04-state-graphs-workflows.md) (30 min)
5. [Advanced RAG](../../docs/tutorials/05-advanced-rag.md) (35 min)
6. [Production Patterns](../../docs/tutorials/06-production-patterns.md) (40 min)

---

## API Methods Quick Reference

See [API Reference](../../docs/08-api-reference.md) for complete details.

**Chains:**
- `Chain::make($agent, $template, $outputKey)` — Create chain
- `$chain->run($inputs)` — Execute synchronously
- `$chain->stream($inputs)` — Execute with streaming
- `$chain->withMemory($memory)` — Add memory
- `$chain->withRetriever($retriever)` — Add retrieval
- `$chain->then($nextChain)` — Compose chains

**State Graphs:**
- `$graph->addNode($name, $callable)` — Add node
- `$graph->addEdge($from, $to)` — Add direct edge
- `$graph->addConditionalEdge($from, $fn)` — Add conditional routing
- `$graph->setEntryPoint($name)` — Set entry point
- `$graph->compile()` — Compile to executable
- `$compiled->invoke($state)` — Execute graph

---

## Examples

Runnable without external credentials:

```bash
php examples/01-basic-chain.php           # Simple Q&A
php examples/02-chain-with-memory.php     # Multi-turn chat
php examples/03-chain-with-retrieval.php  # RAG system
php examples/04-state-graph-workflow.php  # Workflow
php examples/05-manager-and-factory.php   # Fluent API
php examples/fluent-chain-factory.php     # Composition
```

---

## Advanced References

For deep-dive technical details, see [references/](references/):

- **[StateGraph Reference](references/stategraph.md)** — Workflows, nodes, edges, routing, patterns
- **[Chains Reference](references/chains.md)** — Composition, decorators, best practices
- **[Prompts Reference](references/prompts.md)** — Templates, variables, few-shot, chain-of-thought
- **[Memory & Retrieval Reference](references/memory-and-retrieval.md)** — Memory types, custom implementations, RAG
- **[Testing Reference](references/testing.md)** — Mocks, assertions, integration tests

See [references/README.md](references/README.md) for a complete index.
