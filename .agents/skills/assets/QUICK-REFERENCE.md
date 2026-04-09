# Laravel AI Chain — Quick Reference

## Chain Methods

```php
// Create
$chain = Chain::make($agent, $prompt, $outputKey);

// Run
$result = $chain->run(['input' => 'value']);

// Stream
foreach ($chain->stream(['input' => 'value']) as $token) {
    echo $token;
}

// Enhance
$chain->withMemory($memory);
$chain->withRetriever($retriever);
$chain->withProvider('anthropic');
$chain->withModel('claude-3');
```

## StateGraph Methods

```php
// Create
$graph = new StateGraph();

// Add node
$graph->addNode('name', fn(State $s) => $s->with([...]));

// Add edge
$graph->addEdge('from', 'to');

// Conditional edge
$graph->addConditionalEdge('from', fn(State $s) => 
    $condition ? 'next' : StateGraph::END
);

// Compile and run
$result = $graph->compile()->invoke($state);
```

## Memory Types

```php
// In-memory (single request)
$memory = new InMemoryConversation();

// Cache (persistent, with TTL)
$memory = new CacheConversationMemory(key: 'key', ttl: 3600);

// Summary (token efficient)
$memory = new SummaryMemory(maxMessages: 10, agent: $agent);
```

## Retrievers

```php
// Vector search
$retriever = new VectorStoreRetriever($store, topK: 5);

// Hybrid (vector + lexical)
$retriever = new HybridRetriever($vectorRet, $lexicalRet, alpha: 0.6);

// Reranking
$retriever = new RerankingRetriever($baseRet, model: 'cross-encoder');
```

## PromptTemplate

```php
// Create
$template = PromptTemplate::from('Q: {input}');

// Render
$prompt = $template->format(['input' => 'question']);

// Partial
$partial = $template->partial(['prefix' => 'value']);
```

## Common Patterns

**Sequential Chain:**
```php
$pipeline = $chain1->then($chain2)->then($chain3);
```

**Fluent Factory:**
```php
$pipeline = ChainFactory::chain($a1, $p1, 'step1')
    ->thenPrompt($a2, $p2, 'step2')
    ->build();
```

**State Graph:**
```php
$graph->addNode('A', ...);
$graph->addConditionalEdge('A', fn($s) => $s->score > 50 ? 'B' : 'C');
$graph->compile()->invoke($state);
```

**RAG Chain:**
```php
$chain->withRetriever($retriever)
    ->run(['input' => 'query']);
```

**Memory Chain:**
```php
$chain->withMemory($memory)
    ->run(['input' => 'message']);
```

