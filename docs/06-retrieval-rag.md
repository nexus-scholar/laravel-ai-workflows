# Retrieval & RAG

Retrieval-Augmented Generation (RAG) enhances AI responses with retrieved documents. This guide covers retrieval strategies and implementation.

## What is RAG?

RAG has three steps:

1. **Retrieve** — Find relevant documents for the query
2. **Augment** — Add them to the prompt as context
3. **Generate** — AI generates response using both query and context

```
Query: "How do I deploy Laravel?"
        ↓
   [Retriever]
   Finds: [doc1, doc2, doc3]
        ↓
   [Augmentation]
   "Context: doc1...\n\nQuestion: How do I deploy Laravel?"
        ↓
   [Generation]
   AI: "First, prepare your server..."
```

## Retriever Types

### 1. Vector Store Retriever

Semantic search using embeddings:

```php
use Nexus\AiChain\Retrieval\VectorStoreRetriever;

$retriever = new VectorStoreRetriever(
    vectorStore: app('vector-store'),  // From Laravel container
    topK: 5                             // Return top 5 results
);

// Use with chain
$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever);

// When you run the chain with {context} in prompt,
// it automatically retrieves and injects documents
$result = $chain->run(['input' => 'Question here']);
```

### 2. Hybrid Retriever

Combines semantic (vector) + lexical (BM25) search:

```php
use Nexus\AiChain\Retrieval\HybridRetriever;
use Nexus\AiChain\Retrieval\VectorStoreRetriever;
use Nexus\AiChain\Retrieval\LexicalRetriever;

$retriever = new HybridRetriever(
    vectorRetriever: new VectorStoreRetriever($vectorStore),
    lexicalRetriever: new LexicalRetriever($database),
    alpha: 0.5  // 50% vector, 50% lexical
);

$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever, topK: 10);
```

### 3. Reranking Retriever

Re-scores results using a cross-encoder model:

```php
use Nexus\AiChain\Retrieval\RerankingRetriever;

$retriever = new RerankingRetriever(
    baseRetriever: new VectorStoreRetriever($vectorStore),
    rerankModel: 'cross-encoder/ms-marco-MiniLM-L-12-v2',
    topK: 5
);

// Retrieves many, reranks, returns top 5
$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever);
```

## Building a RAG Chain

### Step 1: Create Vector Store

First, populate your vector store with documents:

```php
use Laravel\Ai\Embeddings;

$documents = [
    'Laravel is a PHP framework...',
    'To install Laravel, run: composer create-project...',
    'The Eloquent ORM provides a simple way to interact...',
];

foreach ($documents as $doc) {
    $embedding = Embeddings::model('text-embedding-ada-002')
        ->embed($doc)
        ->embedding;
    
    $vectorStore->add(
        id: md5($doc),
        text: $doc,
        embedding: $embedding
    );
}
```

### Step 2: Create Retriever

```php
use Nexus\AiChain\Retrieval\VectorStoreRetriever;

$retriever = new VectorStoreRetriever(
    vectorStore: app('vector-store'),
    topK: 3
);
```

### Step 3: Design Prompt with Context

```php
$prompt = PromptTemplate::from(<<<'PROMPT'
You are a Laravel expert.

Reference materials:
{context}

User question: {input}

Based on the reference materials above, answer the question.
PROMPT);
```

### Step 4: Create Chain

```php
use Nexus\AiChain\Chains\Chain;
use function Laravel\Ai\agent;

$chain = Chain::make(
    agent(instructions: 'Be helpful and accurate.'),
    $prompt
)->withRetriever($retriever);
```

### Step 5: Run

```php
$result = $chain->run([
    'input' => 'How do I install Laravel?'
]);

// The chain automatically:
// 1. Retrieves 3 documents matching "How do I install Laravel?"
// 2. Injects them as {context}
// 3. Passes to agent
// 4. Returns response

echo $result;
```

## Context Formatting

### Default Formatting

```php
// Retrieved documents are formatted as:
$context = implode("\n\n", [
    "[1] Laravel is a PHP framework...",
    "[2] To install Laravel, run: composer...",
    "[3] The installation takes about 5 minutes...",
]);
```

### Custom Formatting

```php
use Nexus\AiChain\Retrieval\VectorStoreRetriever;

class CustomRetriever extends VectorStoreRetriever
{
    protected function formatContext(array $documents): string
    {
        return implode("\n", array_map(
            fn($doc, $i) => "**Document " . ($i + 1) . "**\n" . 
                           $doc['text'] . 
                           "\n(Relevance: " . round($doc['score'] * 100) . "%)",
            $documents,
            array_keys($documents)
        ));
    }
}

$retriever = new CustomRetriever($vectorStore);
```

## Advanced RAG Patterns

### Pattern 1: Multi-Query RAG

Retrieve from multiple angles:

```php
class MultiQueryRetriever
{
    private VectorStoreRetriever $retriever;
    private Agent $agent;

    public function __construct($vectorStore)
    {
        $this->retriever = new VectorStoreRetriever($vectorStore);
        $this->agent = agent(instructions: 'Generate 3 alternative search queries');
    }

    public function retrieve(string $question): array
    {
        // Generate alternative queries
        $prompt = PromptTemplate::from(
            'Given the question: {question}, generate 3 alternative ways to ask it.'
        );
        
        $agent = $this->agent;
        $response = $agent->prompt($prompt->format(['question' => $question]));
        
        // Parse alternative queries (simplified)
        $alternatives = explode("\n", $response);
        
        // Retrieve for each query
        $allDocs = [];
        foreach ([$question, ...$alternatives] as $q) {
            $docs = $this->retriever->retrieve($q, topK: 5);
            $allDocs = array_merge($allDocs, $docs);
        }
        
        // Deduplicate and return top K
        return array_slice(
            array_unique($allDocs, SORT_REGULAR),
            0,
            10
        );
    }
}
```

### Pattern 2: Hierarchical Retrieval

Retrieve summaries first, then full documents:

```php
class HierarchicalRetriever
{
    private VectorStoreRetriever $summaryRetriever;
    private VectorStoreRetriever $detailRetriever;

    public function __construct($summaryStore, $detailStore)
    {
        $this->summaryRetriever = new VectorStoreRetriever($summaryStore);
        $this->detailRetriever = new VectorStoreRetriever($detailStore);
    }

    public function retrieve(string $query, int $topK = 5): array
    {
        // Step 1: Retrieve summaries
        $summaries = $this->summaryRetriever->retrieve($query, topK: $topK);
        
        // Step 2: Get full documents for relevant summaries
        $details = [];
        foreach ($summaries as $summary) {
            $full = $this->detailRetriever->retrieve($summary['id'], topK: 1);
            $details = array_merge($details, $full);
        }
        
        return array_slice($details, 0, $topK);
    }
}
```

### Pattern 3: Adaptive RAG

Decide if retrieval is needed:

```php
$chain = Chain::make($agent, PromptTemplate::from(
    'Question: {input}'
));

$graph = new StateGraph();

final class AdaptiveState extends State
{
    public function __construct(
        public string $input = '',
        public bool $needsRetrieval = false,
        public array $context = [],
        public string $answer = '',
    ) {}

    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'needsRetrieval' => $this->needsRetrieval,
            'context' => $this->context,
            'answer' => $this->answer,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            input: $data['input'] ?? '',
            needsRetrieval: $data['needsRetrieval'] ?? false,
            context: $data['context'] ?? [],
            answer: $data['answer'] ?? '',
        );
    }
}

$graph->addNode('analyze', fn(AdaptiveState $s) => {
    // Decide if question needs external knowledge
    $keywords = ['what', 'how', 'why', 'when'];
    $needsIt = count(array_filter(
        $keywords,
        fn($k) => str_contains(strtolower($s->input), $k)
    )) > 0;
    
    return $s->with(['needsRetrieval' => $needsIt]);
});

$graph->addNode('retrieve', fn(AdaptiveState $s) => {
    $docs = $retriever->retrieve($s->input, topK: 5);
    return $s->with(['context' => $docs]);
});

$graph->addNode('generate', fn(AdaptiveState $s) => {
    $prompt = $s->needsRetrieval 
        ? "Context: {context}\n\nQuestion: {input}"
        : "Question: {input}";
    
    $answer = $chain->run([
        'input' => $s->input,
        'context' => formatContext($s->context)
    ]);
    
    return $s->with(['answer' => $answer]);
});

$graph->setEntryPoint('analyze');
$graph->addEdge('analyze', 'retrieve');
$graph->addConditionalEdge('retrieve', fn(AdaptiveState $s) =>
    $s->context ? 'generate' : StateGraph::END
);
$graph->addEdge('generate', StateGraph::END);
```

## RAG with Memory

Combine retrieval and conversation history:

```php
$memory = new CacheConversationMemory(key: "user.$userId");
$retriever = new VectorStoreRetriever($vectorStore);

$prompt = PromptTemplate::from(<<<'PROMPT'
Documents:
{context}

Chat history:
{history}

New question: {input}
PROMPT);

$chain = Chain::make($agent, $prompt)
    ->withMemory($memory)
    ->withRetriever($retriever);

// Each call retrieves docs + sees chat history
$answer = $chain->run(['input' => 'Tell me more about that']);
$memory->add('user', 'Tell me more about that');
$memory->add('assistant', $answer);
```

## Document Types

### Document Model

```php
use Nexus\AiChain\Retrieval\Document;

$doc = new Document(
    id: 'doc-123',
    text: 'Full document text...',
    metadata: [
        'source' => 'user_manual.pdf',
        'page' => 42,
        'date' => '2024-01-15',
    ],
    score: 0.87  // Relevance score
);
```

### Retrieving Documents

```php
$documents = $retriever->retrieve(
    query: 'How do I deploy?',
    topK: 5
);

foreach ($documents as $doc) {
    echo $doc->id;              // doc-123
    echo $doc->text;            // Full text
    echo $doc->metadata['page']; // 42
    echo $doc->score;           // 0.87
}
```

## Performance Optimization

### Batching Retrievals

```php
$queries = ['Q1', 'Q2', 'Q3'];

// Batch retrieve to reduce overhead
$retriever = new VectorStoreRetriever($vectorStore);
$allResults = [];

foreach ($queries as $q) {
    $allResults[$q] = $retriever->retrieve($q, topK: 3);
}
```

### Caching Retrieved Documents

```php
class CachedRetriever implements Retriever
{
    private array $cache = [];

    public function retrieve(string $query, int $topK = 5): array
    {
        $key = md5($query . $topK);
        
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        $results = $this->baseRetriever->retrieve($query, $topK);
        $this->cache[$key] = $results;
        
        return $results;
    }
}
```

---

**Next:** [Advanced Patterns](./07-advanced-patterns.md) →

