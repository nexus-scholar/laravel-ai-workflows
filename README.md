# Laravel AI Chain

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square)
![Laravel](https://img.shields.io/badge/Laravel-12.0%20%7C%2013.0-FF2D20?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

**Orchestration framework for `laravel/ai`**

[📖 Docs](#documentation) • [🚀 Install](#installation) • [💡 Examples](#examples)

</div>

---

## Overview

Laravel AI Chain is a composition framework for building AI workflows with `laravel/ai`. 

Build chains, state graphs, memory-driven conversations, and retrieval-augmented generation (RAG)—with Laravel's fluent syntax and type safety.

```php
// Simple chain
$chain = Chain::make(
    agent(instructions: 'You are helpful'),
    PromptTemplate::from('Q: {input}'),
    outputKey: 'answer'
)->run(['input' => 'What is RAG?']);

// Multi-stage pipeline
$pipeline = ChainFactory::chain($agent1, $p1, 'draft')
    ->thenPrompt($agent2, $p2, 'edited')
    ->thenPrompt($agent3, $p3, 'final')
    ->build();

// Add memory
$chain->withMemory(new CacheConversationMemory(key: "chat.$userId"));

// State graphs with routing
$graph->addNode('process', fn($s) => /* ... */);
$graph->addConditionalEdge('process', fn($s) => $s->score > 80 ? 'approve' : 'reject');

// RAG
$chain->withRetriever(new VectorStoreRetriever($store, topK: 5));
```

---

## Installation

```bash
composer require nexus/laravel-ai-chain

# Optional: publish config
php artisan vendor:publish --tag=ai-chain-config
```


---

## Key Concepts

- **Chains** — Sequential agent → prompt → output flows
- **State Graphs** — Deterministic workflows with conditional routing
- **Memory** — Persistent conversation context
- **Retriever** — Document search & injection for RAG
- **State** — Type-safe, immutable workflow data

See [Core Concepts](./docs/02-core-concepts.md) for detailed explanations.

---

## Documentation

- **[Getting Started](./docs/01-getting-started.md)** — Installation & first steps
- **[Core Concepts](./docs/02-core-concepts.md)** — Chains, graphs, memory
- **[Chains Guide](./docs/03-chains-guide.md)** — Building & composing chains
- **[State Graphs](./docs/04-state-graphs.md)** — Workflows & routing
- **[Memory Systems](./docs/05-memory-systems.md)** — Persistence strategies
- **[Retrieval & RAG](./docs/06-retrieval-rag.md)** — Vector search & integration
- **[Advanced Patterns](./docs/07-advanced-patterns.md)** — Queues, checkpointing, monitoring
- **[API Reference](./docs/08-api-reference.md)** — Complete API

**[6 Progressive Tutorials](./docs/tutorials/)** — From simple chains to production RAG systems

---

## Examples

Runnable examples without external credentials:

```bash
php examples/01-basic-chain.php
php examples/02-chain-with-memory.php
php examples/03-chain-with-retrieval.php
php examples/04-state-graph-workflow.php
php examples/05-manager-and-factory.php
php examples/fluent-chain-factory.php
```

See [examples/](./examples/) directory.

---

## Architecture

Laravel AI Chain is built on three core principles:

1. **Immutable State** — Every workflow transition creates new state; no mutations
2. **Type Safety** — Strict typing validated at runtime across all boundaries
3. **Composability** — Small, single-purpose units combine into complex workflows

See [Core Concepts](./docs/02-core-concepts.md) for design philosophy.

---

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md) for guidelines.

## License

MIT — See [LICENSE](./LICENSE).

## Acknowledgments

- Built on [laravel/ai](https://github.com/laravel/ai)
- Inspired by [LangChain](https://www.langchain.com/) & [LangGraph](https://langchain-ai.github.io/langgraph/)
- Part of [Nexus](https://github.com/mouadh/nexus)

---

<div align="center">

[📖 Documentation](./docs/README.md) • [💡 Examples](./examples/) • [🤝 Contribute](./CONTRIBUTING.md)

</div>
