# Tutorial 5: Advanced RAG & Retrieval (35 minutes)

In this tutorial, you'll build retrieval-augmented generation (RAG) systems that combine chains with document retrieval.

## Prerequisites

✅ Complete [Tutorial 4: State Graphs](./04-state-graphs-workflows.md)  

## What You'll Learn

- Set up vector stores
- Implement RAG chains
- Use hybrid and reranking retrievers
- Combine memory, retrieval, and chains

## Step 1: Basic RAG Chain

Create `app/Examples/SimpleRAG.php`:

```php
<?php

namespace App\Examples;

use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Prompts\PromptTemplate;
use Nexus\AiChain\Retrieval\VectorStoreRetriever;
use function Laravel\Ai\agent;

class SimpleRAG
{
    public static function run()
    {
        // 1️⃣ Get your vector store from the container
        $vectorStore = app('vector-store');  // Must be registered in Laravel

        // 2️⃣ Create a retriever
        $retriever = new VectorStoreRetriever(
            vectorStore: $vectorStore,
            topK: 5  // Retrieve top 5 documents
        );

        // 3️⃣ Create a prompt with {context} placeholder
        $prompt = PromptTemplate::from(<<<'PROMPT'
You are a helpful documentation assistant.

Reference documents:
{context}

User question: {input}

Based ONLY on the reference documents above, answer the question.
PROMPT);

        // 4️⃣ Create chain with retriever
        $chain = Chain::make(
            agent(instructions: 'Answer accurately based on context.'),
            $prompt
        )->withRetriever($retriever);

        // 5️⃣ Run! The chain automatically:
        //    - Retrieves documents matching {input}
        //    - Injects them as {context}
        //    - Gets AI response
        $result = $chain->run(['input' => 'How do I install Laravel?']);

        echo $result;
    }
}
```

How it works:

```
Input: "How do I install Laravel?"
            ↓
   [Retriever.retrieve()]
   Finds 5 docs about Laravel installation
            ↓
   [Inject as {context}]
   "Reference documents: [doc1, doc2, ...]"
            ↓
   [Chain.run()]
   AI reads context and answers
            ↓
   Output: "To install Laravel, run: composer create-project..."
```

## Step 2: Setting Up a Vector Store

### Using Milvus (Recommended)

```php
// In your Laravel service provider

use Laravel\Ai\Embeddings;

app()->singleton('vector-store', function () {
    // In reality, you'd use a proper vector DB
    // This is simplified - see your vector store docs
    return new MilvusVectorStore(
        host: env('MILVUS_HOST', 'localhost'),
        port: env('MILVUS_PORT', 19530),
    );
});

// Populate it with documents
$documents = [
    'Laravel is a PHP framework for building web applications.',
    'To install Laravel, run: composer create-project laravel/laravel myapp',
    'Eloquent is Laravels ORM for database operations.',
    'Routing in Laravel maps URLs to controller actions.',
    'Middleware allows you to filter HTTP requests.',
];

foreach ($documents as $doc) {
    $embedding = Embeddings::model('text-embedding-ada-002')
        ->embed($doc)
        ->embedding;
    
    app('vector-store')->add(
        id: md5($doc),
        text: $doc,
        embedding: $embedding
    );
}
```

## Step 3: Hybrid Retrieval

Combine semantic (vector) + lexical (keyword) search:

```php
use Nexus\AiChain\Retrieval\HybridRetriever;

class HybridRAG
{
    public static function run()
    {
        // Combine two retrieval strategies
        $retriever = new HybridRetriever(
            vectorRetriever: new VectorStoreRetriever(app('vector-store')),
            lexicalRetriever: new LexicalRetriever(DB::connection()),
            alpha: 0.6  // 60% vector, 40% lexical
        );

        $prompt = PromptTemplate::from(
            'Documents:\n{context}\n\nQuestion: {input}'
        );

        $chain = Chain::make(agent(), $prompt)
            ->withRetriever($retriever, topK: 5);

        $result = $chain->run(['input' => 'How do I use migrations?']);
        echo $result;
    }
}
```

Benefits:
- **Vector search** captures semantic meaning ("similar concepts")
- **Lexical search** captures exact keyword matches
- **Hybrid** gets best of both worlds

## Step 4: Reranking for Better Results

```php
use Nexus\AiChain\Retrieval\RerankingRetriever;

class RerankingRAG
{
    public static function run()
    {
        // Retrieve many, rerank, return top K
        $retriever = new RerankingRetriever(
            baseRetriever: new VectorStoreRetriever(app('vector-store')),
            rerankModel: 'cross-encoder/ms-marco-MiniLM-L-12-v2',
            topK: 3
        );

        $prompt = PromptTemplate::from(
            'Relevant docs:\n{context}\n\nQuestion: {input}'
        );

        $chain = Chain::make(agent(), $prompt)
            ->withRetriever($retriever);

        // Process:
        // 1. Retriever gets 20 docs
        // 2. Reranker scores all 20
        // 3. Returns top 3 by score
        $result = $chain->run(['input' => 'Explain middleware']);
        echo $result;
    }
}
```

## Step 5: RAG + Memory (Conversational RAG)

Combine retrieval and conversation memory:

```php
use Nexus\AiChain\Memory\CacheConversationMemory;

class ConversationalRAG
{
    public static function chat(string $userId, string $question): string
    {
        // Memory for conversation history
        $memory = new CacheConversationMemory(
            key: "chat.$userId",
            ttl: 86400
        );

        // Retriever for documents
        $retriever = new VectorStoreRetriever(app('vector-store'), topK: 5);

        // Prompt includes both memory and context
        $prompt = PromptTemplate::from(<<<'PROMPT'
Reference documents:
{context}

Chat history:
{history}

User: {input}

Answer based on documents and conversation history.
PROMPT);

        // Chain with both memory and retriever
        $chain = Chain::make(agent(), $prompt)
            ->withMemory($memory)
            ->withRetriever($retriever);

        // Run
        $response = $chain->run(['input' => $question]);

        // Save to memory
        $memory->add('user', $question);
        $memory->add('assistant', (string)$response);

        return $response;
    }
}

// Usage
echo ConversationalRAG::chat('user123', 'What is a controller?');
echo ConversationalRAG::chat('user123', 'How do I define one?');  // Sees history!
```

## Step 6: Multi-Query RAG (Retrieve Multiple Perspectives)

```php
class MultiQueryRAG
{
    private VectorStoreRetriever $retriever;

    public function __construct()
    {
        $this->retriever = new VectorStoreRetriever(app('vector-store'));
    }

    public function retrieve(string $question, int $topK = 5): array
    {
        // Generate alternative queries
        $agent = agent(instructions: 'Generate 3 alternative ways to ask this.');
        $prompt = PromptTemplate::from(
            'Original question: {question}\n\nGenerate alternatives:'
        );

        $alternatives = $agent->prompt($prompt->format(['question' => $question]));
        // Parse alternatives (simplified - in reality, parse more carefully)
        $queries = [$question, ...$alternatives];

        // Retrieve with all queries
        $allDocs = [];
        foreach ($queries as $q) {
            $docs = $this->retriever->retrieve($q, topK: 5);
            array_push($allDocs, ...$docs);
        }

        // Deduplicate and return top K
        $unique = [];
        $seen = [];
        foreach ($allDocs as $doc) {
            if (!in_array($doc['id'], $seen)) {
                $unique[] = $doc;
                $seen[] = $doc['id'];
                if (count($unique) >= $topK) break;
            }
        }

        return $unique;
    }
}

// Use it
$rag = new MultiQueryRAG();
$docs = $rag->retrieve('What is dependency injection?', topK: 5);
```

## Step 7: Real-World: Documentation Q&A

```php
class DocumentationQA
{
    public function answer(string $question): string
    {
        $retriever = new VectorStoreRetriever(app('vector-store'), topK: 3);

        $prompt = PromptTemplate::from(<<<'PROMPT'
You are a Laravel documentation expert.

Relevant sections from Laravel docs:
{context}

User question: {input}

Provide a clear, accurate answer. If the answer is not in the docs, say so.
PROMPT);

        $chain = Chain::make(
            agent(instructions: 'Be accurate and helpful.'),
            $prompt
        )->withRetriever($retriever);

        return $chain->run(['input' => $question]);
    }
}

// Endpoint
Route::get('/docs/ask', function (Request $request) {
    $qa = new DocumentationQA();
    $answer = $qa->answer($request->input('q'));

    return response()->json(['answer' => $answer]);
});
```

## Step 8: Streaming RAG Responses

```php
class StreamingRAG
{
    public function stream(string $question)
    {
        $retriever = new VectorStoreRetriever(app('vector-store'), topK: 5);

        $prompt = PromptTemplate::from(
            'Docs:\n{context}\n\nQuestion: {input}'
        );

        $chain = Chain::make(agent(), $prompt)
            ->withRetriever($retriever);

        // Stream tokens as they arrive
        foreach ($chain->stream(['input' => $question]) as $token) {
            echo $token;
            flush();
        }
    }
}

// In controller
return response()->stream(function () use ($question) {
    (new StreamingRAG())->stream($question);
}, 200, [
    'Content-Type' => 'text/event-stream',
    'Cache-Control' => 'no-cache',
]);
```

## Practical Exercises

### Exercise 1: Product Help Center
Build a RAG system that:
1. Retrieves product docs
2. Answers customer questions
3. Tracks conversation history

```php
// 👉 Your code here
```

### Exercise 2: Code Reference Tool
Build a RAG system that:
1. Retrieves function/class docs
2. Shows usage examples
3. Provides copy-paste code

```php
// 👉 Your code here
```

### Exercise 3: Research Paper Q&A
Build a RAG system that:
1. Chunks research papers
2. Retrieves relevant sections
3. Summarizes findings

```php
// 👉 Your code here
```

## Common Issues

### Issue: "No documents retrieved"

**Cause:** Vector store is empty or query doesn't match.

**Solution:** Check vector store population:

```php
// Verify documents are in the store
$allDocs = $vectorStore->all();
echo count($allDocs);  // Should be > 0

// Test retrieval directly
$docs = $retriever->retrieve('test query');
echo count($docs);  // Should be > 0
```

### Issue: "Irrelevant documents"

**Cause:** Vector embeddings don't capture meaning.

**Solution:** Use better embedding model or add metadata:

```php
// Better embedding model
$embedding = Embeddings::model('text-embedding-3-large')
    ->embed($document)
    ->embedding;

// Add metadata for filtering
$vectorStore->add(
    id: $id,
    text: $document,
    embedding: $embedding,
    metadata: ['source' => 'docs', 'category' => 'installation']
);
```

## Key Takeaways

✅ RAG = Retrieve + Augment + Generate  
✅ Retriever finds relevant documents  
✅ Documents injected into prompt as `{context}`  
✅ Combine with memory for conversational RAG  
✅ Use hybrid or reranking for better results  

## Next Steps

→ Ready for production? Go to [Tutorial 6: Production Patterns](./06-production-patterns.md)

Or explore the [API Reference](../08-api-reference.md) for deep dives.

---

**You're almost there!** 🚀

