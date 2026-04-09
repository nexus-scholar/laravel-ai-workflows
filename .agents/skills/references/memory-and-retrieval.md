# Conversation Memory & Retrieval

Guide for managing conversation context and retrieving external data.

## Conversation Memory

Memory persists conversation history for multi-turn interactions.

### Memory Types

**InMemoryConversation** — Non-persistent (single request):
```php
use Nexus\\Workflow\Memory\InMemoryConversation;

$memory = new InMemoryConversation();
$memory->add('user', 'What is PHP?');
$memory->add('assistant', 'PHP is a language...');

echo $memory->asString();
// Output: "user: What is PHP?\nassistant: PHP is..."
```

**CacheConversationMemory** — Persistent via Laravel cache:
```php
use Nexus\\Workflow\Memory\CacheConversationMemory;

$memory = new CacheConversationMemory(
    key: "chat.$userId",
    ttl: 86400  // 24 hours
);

// Persists across requests
$memory->add('user', 'Hello');
// Later session:
$recovered = new CacheConversationMemory(key: "chat.$userId");
// Can recover the message!
```

**SummaryMemory** — Smart token reduction:
```php
use Nexus\\Workflow\Memory\SummaryMemory;
use function Laravel\Ai\agent;

$memory = new SummaryMemory(
    maxMessages: 10,
    agent: agent(instructions: 'Summarize concisely')
);

// After many messages, keeps recent ones and summarizes old ones
// Reduces token usage for long conversations
```

### Using Memory with Chains

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;

$memory = new CacheConversationMemory(key: "user.$userId");

$prompt = PromptTemplate::from(
    'History:\n{history}\n\nNew: {input}'
);

$chain = Chain::make($agent, $prompt)
    ->withMemory($memory);

// Each call sees previous messages
$chain->run(['input' => 'What is RAG?']);
$memory->add('user', 'What is RAG?');
$memory->add('assistant', 'RAG is...');

$chain->run(['input' => 'How does it work?']);  // Sees history
```

### Memory Interface

All memory implements `Nexus\\Workflow\Contracts\Memory`:

```php
interface Memory {
    public function add(string $role, string $content): void;
    public function messages(): array;  // Returns [['role' => 'user', 'content' => '...']]
    public function clear(): void;
    public function asString(): string;  // Formatted for prompt injection
}
```

Create custom implementations:

```php
class DatabaseMemory implements Memory
{
    public function __construct(private int $conversationId, private PDO $db) {}
    
    public function add(string $role, string $content): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$this->conversationId, $role, $content]);
    }
    
    // ... implement other methods
}
```

## Retrieval-Augmented Generation (RAG)

Retrievers inject external data into prompts for context-aware responses.

### Retriever Types

**VectorStoreRetriever** — Semantic search via embeddings:
```php
use Nexus\\Workflow\Retrieval\VectorStoreRetriever;

$retriever = new VectorStoreRetriever(
    vectorStore: app('vector-store'),
    topK: 5
);

$documents = $retriever->retrieve('How do I deploy?');
```

**HybridRetriever** — Vector + lexical search:
```php
use Nexus\\Workflow\Retrieval\HybridRetriever;

$retriever = new HybridRetriever(
    vectorRetriever: new VectorStoreRetriever($vectorStore),
    lexicalRetriever: new LexicalRetriever($db),
    alpha: 0.6  // 60% vector, 40% lexical
);
```

**RerankingRetriever** — Re-score results:
```php
use Nexus\\Workflow\Retrieval\RerankingRetriever;

$retriever = new RerankingRetriever(
    baseRetriever: new VectorStoreRetriever($store),
    rerankModel: 'cross-encoder-model',
    topK: 5
);

// Gets 20 results, reranks, returns top 5
```

### Using Retrievers with Chains

```php
$prompt = PromptTemplate::from(
    'Context:\n{context}\n\nQuestion: {input}'
);

$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever, topK: 5);

// Chain automatically:
// 1. Retrieves top 5 documents for {input}
// 2. Formats as {context}
// 3. Passes to agent
$answer = $chain->run(['input' => 'How do I deploy Laravel?']);
```

### Document Model

Retrievers return `Nexus\\Workflow\Retrieval\Document`:

```php
class Document
{
    public string $id;           // Unique identifier
    public string $text;         // Document content
    public array $metadata;      // Extra info (source, page, etc)
    public float $score;         // Relevance score (0-1)
}
```

### Custom Retriever

Implement `Nexus\\Workflow\Contracts\Retriever`:

```php
class DatabaseRetriever implements Retriever
{
    public function retrieve(string $query, int $topK = 5): array
    {
        $results = DB::table('documents')
            ->where('content', 'like', "%$query%")
            ->limit($topK)
            ->get();
        
        return array_map(fn($row) => new Document(
            id: $row->id,
            text: $row->content,
            metadata: ['source' => $row->source],
            score: 0.5
        ), $results->all());
    }
}
```

## Memory + Retrieval Combined

Multi-turn conversations with external knowledge:

```php
$memory = new CacheConversationMemory(key: "chat.$userId");
$retriever = new VectorStoreRetriever($store, topK: 5);

$prompt = PromptTemplate::from(
    'Documents:\n{context}\n\nHistory:\n{history}\n\nQ: {input}'
);

$chain = Chain::make($agent, $prompt)
    ->withMemory($memory)
    ->withRetriever($retriever);

// Each call:
// - Retrieves relevant docs
// - Includes conversation history
// - Generates context-aware answer
$answer = $chain->run(['input' => 'How do I...?']);
$memory->add('user', 'How do I...?');
$memory->add('assistant', $answer);
```

## Best Practices

**Memory:**
- Use `CacheConversationMemory` for multi-request persistence
- Use `SummaryMemory` for long conversations (cost/token efficiency)
- Always clean up old conversations to save storage

**Retrieval:**
- Use vector search for semantic matching (similar concepts)
- Use hybrid search when keywords matter (exact matches)
- Use reranking for high-relevance requirements
- Implement custom retrievers for domain-specific data

**Combined:**
- Memory = context from conversation
- Retrieval = context from external data
- Together = powerful, context-aware systems

See [Memory Systems](../../docs/05-memory-systems.md) and [Retrieval & RAG](../../docs/06-retrieval-rag.md) for detailed guides.
