# References Index

Deep-dive technical reference materials for Laravel AI Chain.

Use these when you need detailed information about specific topics.

## Quick Reference

| Topic | File | When to Use |
|-------|------|------------|
| **Building Workflows** | [StateGraph](./stategraph.md) | Creating nodes, edges, routing, patterns |
| **Building Chains** | [Chains](./chains.md) | Composing chains, sequencing, decorators |
| **Writing Prompts** | [Prompts](./prompts.md) | Templates, variables, RAG, few-shot |
| **Managing Memory** | [Memory & Retrieval](./memory-and-retrieval.md) | Conversation history, custom memory, RAG |
| **Testing** | [Testing](./testing.md) | Testing chains, graphs, mocks, assertions |

---

## StateGraph Reference

**File:** [stategraph.md](./stategraph.md)

For building deterministic workflows with state transitions.

**Covers:**
- Immutable state pattern
- Node types (processing, conditional, looping)
- Graph building (add nodes, edges, compile, invoke)
- Custom state design
- Execution modes (sync, streaming)
- Advanced patterns (checkpointing, queues, error handling)
- Performance considerations
- Common patterns

**Use when:**
- Creating approval workflows
- Building multi-agent coordination
- Implementing data pipelines
- Handling conditional routing
- Need deterministic, step-by-step execution

---

## Chains Reference

**File:** [chains.md](./chains.md)

For connecting agents to prompts and composing chains.

**Covers:**
- Chain basics (make, run, stream)
- Sequential composition (then, key flow)
- Fluent factory (ChainFactory)
- Advanced patterns (conditional, retry, logging, caching)
- Best practices
- Common patterns

**Use when:**
- Building simple Q&A systems
- Creating multi-stage pipelines
- Composing multiple chains
- Adding memory or retrieval
- Implementing decorators

---

## Prompts Reference

**File:** [prompts.md](./prompts.md)

For creating and managing prompt templates.

**Covers:**
- Template basics (PromptTemplate::from, format, variables)
- Variable syntax and validation
- Partial templates
- Using with chains
- Advanced patterns (few-shot, chain-of-thought, RAG, memory)
- Best practices
- Common patterns

**Use when:**
- Creating new prompt templates
- Implementing few-shot learning
- Building chain-of-thought patterns
- Injecting context (RAG, memory, history)
- Understanding variable syntax

---

## Memory & Retrieval Reference

**File:** [memory-and-retrieval.md](./memory-and-retrieval.md)

For managing conversation context and external data.

**Covers:**
- Conversation memory types (InMemory, Cache, Summary)
- Memory methods and interface
- Custom memory implementations
- Retriever types (Vector, Hybrid, Reranking)
- Document model
- Custom retrievers
- Using retrievers with chains
- Combined memory + retrieval
- Best practices

**Use when:**
- Building multi-turn conversations
- Reducing token usage in long conversations
- Implementing RAG systems
- Creating custom memory backends
- Building domain-specific retrievers

---

## Testing Reference

**File:** [testing.md](./testing.md)

For testing chains, graphs, and workflows.

**Covers:**
- Testing setup (Pest)
- Basic chain testing with mock agents
- Multi-turn chain tests
- State graph testing (simple, conditional routing)
- Memory testing (in-memory, cache)
- Mock retriever testing
- Integration testing (full chains, full workflows)
- Best practices
- Running tests

**Use when:**
- Writing tests for chains
- Testing workflows
- Creating mocks and fixtures
- Testing state transitions
- Verifying routing logic

---

## Learning Path

### Beginner
1. [Chains](./chains.md) — simple composition
2. [Prompts](./prompts.md) — understand templates
3. [Testing](./testing.md) — test everything

### Intermediate
1. [StateGraph](./stategraph.md) — workflow patterns
2. [Memory & Retrieval](./memory-and-retrieval.md) — context
3. Implement full workflows with testing

### Advanced
1. Master all references
2. Study examples (see [../../examples/](../../examples/))
3. Review documentation guides (see [../../docs/](../../docs/))
4. Build custom implementations

---

## Quick Access

**By Topic:**

| Topic | Reference |
|-------|-----------|
| Immutability | StateGraph, Chains |
| State | StateGraph |
| Nodes | StateGraph, Testing |
| Routing | StateGraph |
| Composition | Chains |
| Templates | Prompts |
| Memory | Memory & Retrieval |
| Retrieval | Memory & Retrieval |
| Testing | Testing |

---

**Last Updated:** April 9, 2026

