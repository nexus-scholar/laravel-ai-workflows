# Laravel AI Chain Documentation

Welcome to the **laravel-ai-workflows** documentation! This package provides a production-hardened composition layer on top of `laravel/ai`, enabling you to build complex AI workflows with chains, state machines, memory, and retrieval-augmented generation (RAG).

## 📚 What's Inside

This documentation covers:

- **[Getting Started](./01-getting-started.md)** — Installation, configuration, and your first chain
- **[Core Concepts](./02-core-concepts.md)** — Chains, State Graphs, Memory, and Retrieval
- **[Chains Guide](./03-chains-guide.md)** — Building, composing, and running chains
- **[State Graphs](./04-state-graphs.md)** — Creating deterministic workflows with nodes and edges
- **[Memory Systems](./05-memory-systems.md)** — Conversation memory, summary memory, and caching
- **[Retrieval & RAG](./06-retrieval-rag.md)** — Vector stores, reranking, and context injection
- **[Advanced Patterns](./07-advanced-patterns.md)** — Queue workers, checkpointing, and error handling
- **[API Reference](./08-api-reference.md)** — Complete method signatures and contracts
- **[Tutorials](./tutorials/)** — Step-by-step learning path from beginner to advanced

## 🚀 Quick Start

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;

use function Laravel\Ai\agent;

// Create a simple chain
$chain = Chain::make(
    agent(instructions: 'You are a helpful assistant.'),
    PromptTemplate::from('Question: {input}'),
    outputKey: 'answer'
);

// Run the chain
$result = $chain->run(['input' => 'What is RAG?']);
```

## 📖 Learning Path

### Beginners
1. Start with [Getting Started](./01-getting-started.md)
2. Follow [Core Concepts](./02-core-concepts.md)
3. Work through [Beginner Tutorial](./tutorials/01-beginner-to-advanced.md)

### Intermediate
1. Deep dive into [Chains Guide](./03-chains-guide.md)
2. Explore [Memory Systems](./05-memory-systems.md)
3. Try the [Intermediate Tutorial](./tutorials/02-memory-and-composition.md)

### Advanced
1. Master [State Graphs](./04-state-graphs.md)
2. Implement [Retrieval & RAG](./06-retrieval-rag.md)
3. Study [Advanced Patterns](./07-advanced-patterns.md)
4. Complete the [Advanced Tutorial](./tutorials/03-advanced-workflows.md)

## 🎯 Key Features

- **Type-Safe Chains** — Immutable state, strict typing, fluent API
- **Deterministic Workflows** — StateGraph with nodes, edges, and conditional routing
- **Memory Management** — Conversation memory, summaries, and caching strategies
- **RAG Support** — Vector stores, hybrid retrieval, and reranking
- **Queue-Ready** — Distributed execution with resolver-based dispatch
- **Production-Hardened** — Checkpoint support, error handling, and validation

## 🔗 Package Relationships

`laravel-ai-workflows` sits in the Nexus ecosystem:

```
laravel/ai (foundation)
    ↓
laravel-ai-workflows (composition + workflows)
    ↓
laravel-ai-workflows
    ├→ Used by: nexus-agents (SLR orchestration)
    ├→ Used by: nexus-php (search coordination)
    └→ Built on: graph-algorithms (PageRank, Louvain)
```

## 📦 Installation

```bash
composer require nexus/laravel-ai-workflows
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=ai-chain-config
```

## ⚙️ Configuration

See [config/ai-chain.php](../config/ai-chain.php) for:

- Memory backends (cache, in-memory)
- Retriever strategies (vector, hybrid)
- Graph execution options (checkpointing, streaming)
- Queue runner settings

## 🤝 Contributing

Issues, suggestions, and PRs welcome! See the main [CONTRIBUTING.md](../CONTRIBUTING.md).

## 📄 License

MIT — see [LICENSE](../LICENSE).

---

**Ready to start?** Begin with [Getting Started](./01-getting-started.md)! 🚀

