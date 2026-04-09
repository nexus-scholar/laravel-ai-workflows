# References Index

Deep-dive technical reference materials for laravel-ai-chain.

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

**Example:**
```php
$graph->addNode('validate', fn($s) => ...);
$graph->addConditionalEdge('validate', fn($s) => 
    $s->valid ? 'process' : 'reject'
);
```

---

## Chains Reference

**File:** [chains.md](./chains.md)

For connecting agents to prompts and composing chains.

**Covers:**
- Chain basics (make, run, stream)
- Chain methods (inputKeys, outputKey, enhancement)
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
- Implementing decorators (logging, caching, retry)

**Example:**
```php
$pipeline = ChainFactory::chain($agent1, $p1, 'draft')
    ->thenPrompt($agent2, $p2, 'final')
    ->build();
```

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

**Example:**
```php
$template = PromptTemplate::from(
    'Context: {context}\n\nQ: {input}'
);
```

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
- Combining memory and retrieval

**Example:**
```php
$memory = new CacheConversationMemory(key: "chat.$userId");
$retriever = new VectorStoreRetriever($store, topK: 5);

$chain->withMemory($memory)->withRetriever($retriever);
```

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
- Setting up test suites

**Example:**
```php
it('runs chain', function () {
    $agent = new DemoAgent(['Expected response']);
    $chain = Chain::make($agent, $template);
    $result = $chain->run(['input' => 'test']);
    expect($result)->toBe('Expected response');
});
```

---

## Learning Path

### Beginner
1. Start with [Chains Reference](./chains.md) — simple composition
2. Read [Prompts Reference](./prompts.md) — understand templates
3. Follow [Testing Reference](./testing.md) — test everything

### Intermediate
1. Study [StateGraph Reference](./stategraph.md) — workflow patterns
2. Review [Memory & Retrieval Reference](./memory-and-retrieval.md) — context
3. Implement full workflows with testing

### Advanced
1. Master all references
2. Study examples in [../../examples/](../../examples/)
3. Review documentation guides in [../../docs/](../../docs/)
4. Build custom implementations

---

## Cross-References

These references relate to main documentation:

| Reference | Documentation |
|-----------|---|
| StateGraph | [State Graphs](../../docs/04-state-graphs.md) |
| Chains | [Chains Guide](../../docs/03-chains-guide.md) |
| Prompts | [Chains Guide](../../docs/03-chains-guide.md) (section) |
| Memory & Retrieval | [Memory](../../docs/05-memory-systems.md), [Retrieval](../../docs/06-retrieval-rag.md) |
| Testing | [Advanced Patterns](../../docs/07-advanced-patterns.md) (section) |

---

## API Reference

For complete API method signatures, see [API Reference](../../docs/08-api-reference.md).

For tutorials with step-by-step examples, see [Tutorials](../../docs/tutorials/).

---

## Quick Access

**By Use Case:**

| Goal | Reference |
|------|-----------|
| "How do I build a workflow?" | [StateGraph](./stategraph.md) |
| "How do I compose chains?" | [Chains](./chains.md) |
| "How do I write prompts?" | [Prompts](./prompts.md) |
| "How do I add memory?" | [Memory & Retrieval](./memory-and-retrieval.md) |
| "How do I test this?" | [Testing](./testing.md) |

**By Topic:**

| Topic | Reference |
|-------|-----------|
| Immutability | [StateGraph](./stategraph.md#immutable-state-pattern), [Chains](./chains.md) |
| State | [StateGraph](./stategraph.md#state-design) |
| Nodes | [StateGraph](./stategraph.md#node-types), [Testing](./testing.md) |
| Routing | [StateGraph](./stategraph.md#conditional-routers) |
| Composition | [Chains](./chains.md#chain-composition) |
| Templates | [Prompts](./prompts.md) |
| Memory | [Memory & Retrieval](./memory-and-retrieval.md#conversation-memory) |
| Retrieval | [Memory & Retrieval](./memory-and-retrieval.md#retrieval-augmented-generation-rag) |

---

**Last Updated:** April 9, 2026

