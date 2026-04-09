---
name: laravel-ai-workflows
description: Build AI workflows with chains, state graphs, memory, and RAG. Create multi-step AI pipelines, deterministic workflows, and retrieval-augmented generation systems. Use when building conversational AI, content pipelines, data analysis workflows, or complex agent orchestrations.
license: MIT
compatibility: Requires PHP 8.3+, Laravel 12.0+ or 13.0+, laravel/ai 0.4.4+
metadata:
  author: nexus-team
  version: "1.0"
  package: nexus/laravel-ai-workflows
  github: https://github.com/nexus-scholar/laravel-ai-workflows
---

# Laravel AI Workflows

Orchestration framework for building AI workflows with `laravel/ai`. Compose chains, build state graphs, manage conversation memory, and implement retrieval-augmented generation (RAG) with type-safe immutable state.

## Quick Start

### 1. Simple Chain (Q&A)

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

### 2. Multi-Stage Pipeline

```php
use Nexus\\Workflow\Chains\ChainFactory;

$pipeline = ChainFactory::chain($agent1, $p1, 'draft')
    ->thenPrompt($agent2, $p2, 'edited')
    ->thenPrompt($agent3, $p3, 'final')
    ->build();

$result = $pipeline->run(['topic' => 'AI']);
```

### 3. State Graph Workflow

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

### 4. Add Memory for Conversations

```php
use Nexus\\Workflow\Memory\CacheConversationMemory;

$memory = new CacheConversationMemory(key: "chat.$userId", ttl: 86400);

$chain = Chain::make($agent, $prompt)->withMemory($memory);

$chain->run(['input' => 'What is PHP?']);
$chain->run(['input' => 'How do I use it?']);  // Remembers!
```

### 5. Implement RAG

```php
use Nexus\\Workflow\Retrieval\VectorStoreRetriever;

$chain = Chain::make(
    agent(),
    PromptTemplate::from("Context:\n{context}\n\nQ: {input}")
)->withRetriever(new VectorStoreRetriever(app('vector-store'), topK: 5));

$answer = $chain->run(['input' => 'How do I deploy?']);
```

## Core Concepts

### Chains
Linear flows that connect an agent to a prompt template. Chain multiple chains together for pipelines. Supports memory and retrieval injection.

### State Graphs
Deterministic workflows with typed state, nodes, edges, and conditional routing. Build approval workflows, data pipelines, multi-agent coordination.

### Memory
Persistent conversation context. Three types: `InMemoryConversation`, `CacheConversationMemory`, `SummaryMemory`. Create custom implementations via the Memory interface.

### Retrievers
Document search and injection for RAG. Types: `VectorStoreRetriever`, `HybridRetriever`, `RerankingRetriever`. Implement custom retrievers for domain-specific data.

### State
Type-safe, immutable workflow data. Create custom state by extending the State class. Always use `with()` for immutable updates.

## Installation

```bash
composer require nexus/laravel-ai-workflows

# Optional: publish config
php artisan vendor:publish --tag=ai-chain-config
```

## Common Patterns

**Approval Workflow:** Validate → Manager Review → Director Review → Approve/Reject

**Content Pipeline:** Draft → Edit → Publish

**Multi-Turn Chat:** Build chatbots with persistent memory

**RAG System:** Retrieve docs → Inject context → Generate answer

**Data Analysis:** Extract → Clean → Validate → Analyze → Store

## Documentation

- **[Complete Docs](../../docs/)** — 8 comprehensive guides
- **[Tutorials](../../docs/tutorials/)** — 6 hands-on lessons (beginner to advanced)
- **[Examples](../../examples/)** — 6 runnable examples
- **[API Reference](../../docs/08-api-reference.md)** — Complete method signatures
- **[GitHub README](../../README.md)** — Quick overview

## References

Deep-dive technical materials:

- **[State Graphs](references/stategraph.md)** — Workflows, nodes, routing, patterns
- **[Chains](references/chains.md)** — Composition, decorators, best practices
- **[Prompts](references/prompts.md)** — Templates, variables, engineering
- **[Memory & Retrieval](references/memory-and-retrieval.md)** — All types, custom implementations
- **[Testing](references/testing.md)** — Mocks, assertions, patterns

See [references/README.md](references/README.md) for complete index and learning paths.

## Scripts

- **[Validation](scripts/validate.sh)** — Validate skill implementation
- **[Examples](scripts/run-examples.sh)** — Run example scripts
- **[Tests](scripts/run-tests.sh)** — Run test suite

## Key Features

✅ **Type-Safe** — Strict typing with PHP 8.3+  
✅ **Immutable State** — No side effects, easy debugging  
✅ **Composable** — Build small units, combine into workflows  
✅ **Queue-Ready** — Dispatch graphs to workers safely  
✅ **Well-Documented** — 8 guides + 6 tutorials + references  
✅ **Production-Ready** — Powers the Nexus SLR platform  

## Edge Cases

**Deep nesting:** Keep state graphs under 10-15 nodes for clarity  
**Long conversations:** Use `SummaryMemory` to reduce token usage  
**Large results:** Stream output with `$chain->stream()` for real-time feedback  
**Failures:** Use conditional routing for retry logic and error handling  

## When to Use

Use laravel-ai-workflows when you need:

- **Multi-step AI workflows** — Chains for sequential processing
- **Complex branching** — State graphs for conditional routing
- **Conversation memory** — Multi-turn interactions with context
- **Retrieval-augmented generation** — External knowledge injection
- **Type safety** — Guaranteed data structure at each step
- **Queue execution** — Dispatch workflows to workers

Not ideal for:

- Simple single-prompt queries (use `laravel/ai` directly)
- Real-time streaming APIs (consider alternative approaches)

