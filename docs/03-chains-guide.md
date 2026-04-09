# Chains Guide

This guide covers all aspects of building, composing, and executing chains.

## Creating Chains

### Basic Creation

```php
use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

$chain = Chain::make(
    agent: agent(instructions: 'Be helpful'),
    promptTemplate: PromptTemplate::from('Q: {question}'),
    outputKey: 'answer'
);
```

### Using Fluent Composition

For more complex chains, use `ChainFactory`:

```php
use Nexus\AiChain\Chains\ChainFactory;

$chain = ChainFactory::chain(
    agent(),
    PromptTemplate::from('Q: {question}'),
    'answer'
)
->thenPrompt(
    agent(),
    PromptTemplate::from('Refine: {answer}'),
    'refined'
)
->thenPrompt(
    agent(),
    PromptTemplate::from('Final: {refined}'),
    'final'
)
->build();

$result = $chain->run(['question' => 'What is PHP?']);
```

## Chaining Chains

### Sequential Composition

Chain multiple chains with `then()`:

```php
$draft = Chain::make($agent1, PromptTemplate::from('Write: {topic}'), 'draft');
$edit = Chain::make($agent2, PromptTemplate::from('Edit: {draft}'), 'edited');
$final = Chain::make($agent3, PromptTemplate::from('Final: {edited}'), 'final');

$workflow = $draft
    ->then($edit)
    ->then($final);

$result = $workflow->run(['topic' => 'Machine Learning']);
```

### Understanding Key Flow

```
Input: {topic: 'ML'}
  ↓
[Chain1] Write
  ↓ Output key: 'draft'
  ↓ Passes to Chain2
[Chain2] Edit (reads {draft})
  ↓ Output key: 'edited'
  ↓ Passes to Chain3
[Chain3] Final (reads {edited})
  ↓ Output key: 'final'
Output: {final: '...'}
```

## Input and Output Handling

### Understanding Keys

```php
$chain = Chain::make(
    $agent,
    PromptTemplate::from('Context: {context}\nQuestion: {question}'),
    outputKey: 'answer'
);

// Input must provide both context and question
$result = $chain->run([
    'context' => 'The user is a beginner.',
    'question' => 'What is OOP?'
]);

// Output has the key 'answer'
echo $result['answer'];  // Access as array
echo $result;            // Or just cast to string (uses __toString)
```

### Required vs. Optional

The prompt template defines what inputs are required:

```php
$prompt = PromptTemplate::from('Question: {input}');

// This works (all template vars provided)
$chain->run(['input' => 'Hello']);

// This throws (missing 'input')
$chain->run([]);

// This works (extra keys ignored)
$chain->run(['input' => 'Hello', 'extra' => 'ignored']);
```

### Query Input and Output

```php
// Get the input keys this chain needs
$keys = $chain->inputKeys();  // ['context', 'question']

// Get the output key this chain produces
$key = $chain->outputKey();   // 'answer'
```

## Adding Memory

### Conversation Memory

Track chat history:

```php
use Nexus\AiChain\Memory\InMemoryConversation;

$memory = new InMemoryConversation();

$prompt = PromptTemplate::from(<<<'PROMPT'
Previous conversation:
{history}

User message: {input}
PROMPT);

$chain = Chain::make($agent, $prompt)
    ->withMemory($memory);

// First call
$result1 = $chain->run(['input' => 'What is AI?']);
// Memory now contains: [user: What is AI?, assistant: AI is...]

// Second call (AI sees conversation history)
$result2 = $chain->run(['input' => 'Tell me more']);
// Memory contains both exchanges
```

### Cache-Backed Memory

For production, use cache:

```php
use Nexus\AiChain\Memory\CacheConversationMemory;

$memory = new CacheConversationMemory(
    key: 'conversation.user.123',
    ttl: 3600  // 1 hour
);

$chain = Chain::make($agent, $prompt)->withMemory($memory);
```

### Summary Memory

Reduce tokens by summarizing:

```php
use Nexus\AiChain\Memory\SummaryMemory;

$memory = new SummaryMemory(
    maxMessages: 10,  // After 10, summarize oldest
    agent: agent(instructions: 'Summarize concisely')
);

$chain = Chain::make($agent, $prompt)->withMemory($memory);
```

## Adding Retrieval (RAG)

### Vector Store Retriever

```php
use Nexus\AiChain\Retrieval\VectorStoreRetriever;

$retriever = new VectorStoreRetriever(
    vectorStore: app('vector-store'),  // From Laravel container
    topK: 5
);

$prompt = PromptTemplate::from(<<<'PROMPT'
Relevant documents:
{context}

Question: {input}
PROMPT);

$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever);

$result = $chain->run(['input' => 'How does DDD work?']);
// Automatically retrieves 5 documents about DDD as {context}
```

### Hybrid Retriever

Combine vector + BM25 search:

```php
use Nexus\AiChain\Retrieval\HybridRetriever;

$retriever = new HybridRetriever(
    vectorRetriever: new VectorStoreRetriever($vectorStore),
    lexicalRetriever: new LexicalRetriever($database),
    alpha: 0.5  // Weight: 50% vector, 50% lexical
);

$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever, topK: 10);
```

### Reranking Retriever

Re-score results before returning:

```php
use Nexus\AiChain\Retrieval\RerankingRetriever;

$retriever = new RerankingRetriever(
    baseRetriever: new VectorStoreRetriever($vectorStore),
    rerankModel: 'cross-encoder',
    topK: 5
);

$chain = Chain::make($agent, $prompt)
    ->withRetriever($retriever);
```

## Execution Modes

### Synchronous Execution

Waits for full response:

```php
$result = $chain->run(['input' => 'Hello']);
echo $result;  // Full response
```

### Streaming Execution

Yields tokens as they arrive:

```php
foreach ($chain->stream(['input' => 'Hello']) as $token) {
    echo $token;  // Real-time output
    flush();      // Force output buffer flush
}
```

### In a Blade Template

```blade
<div id="response">
    @php
        foreach ($chain->stream(['input' => $userQuery]) as $token) {
            echo $token;
            flush();
        }
    @endphp
</div>
```

## Provider Configuration

### Per-Chain Override

```php
$chain = Chain::make($agent, $prompt)
    ->withProvider('anthropic')
    ->withModel('claude-3-sonnet');
```

### Using Config Defaults

```php
// In config/ai-chain.php
'ai.default_provider' => 'openai',
'ai.default_model' => 'gpt-4',
```

## Error Handling

### Try-Catch Pattern

```php
use Exception;

try {
    $result = $chain->run(['input' => 'Complex question']);
} catch (Exception $e) {
    logger()->error('Chain execution failed', [
        'error' => $e->getMessage(),
        'input' => 'Complex question'
    ]);
    
    return 'Sorry, I could not process that.';
}
```

### Template Validation

```php
$prompt = PromptTemplate::from('Q: {question}');

try {
    $result = $chain->run([]);  // Missing 'question'
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();  // "Missing template variable: question"
}
```

## Practical Examples

### Example 1: Content Creation Pipeline

```php
// Step 1: Generate outline
$outline = Chain::make(
    agent(instructions: 'Structure ideas'),
    PromptTemplate::from('Topic: {topic}'),
    'outline'
);

// Step 2: Write draft
$draft = Chain::make(
    agent(instructions: 'Write engagingly'),
    PromptTemplate::from('Outline:\n{outline}'),
    'draft'
);

// Step 3: Edit & improve
$edit = Chain::make(
    agent(instructions: 'Fix grammar and flow'),
    PromptTemplate::from('Draft:\n{draft}'),
    'final'
);

// Compose
$pipeline = $outline->then($draft)->then($edit);

$result = $pipeline->run(['topic' => 'Machine Learning Basics']);
```

### Example 2: Question Answering with Memory

```php
$memory = new InMemoryConversation();

$qa = Chain::make(
    agent(instructions: 'Answer based on context and history'),
    PromptTemplate::from(<<<'PROMPT'
Chat history:
{history}

Documents:
{context}

Question: {input}
PROMPT)
)
->withMemory($memory)
->withRetriever(new VectorStoreRetriever($vectorStore));

// User interaction loop
foreach ($userQuestions as $question) {
    $answer = $qa->run(['input' => $question]);
    $memory->add('user', $question);
    $memory->add('assistant', $answer);
    
    echo "User: $question\n";
    echo "Bot: $answer\n\n";
}
```

### Example 3: Multi-Agent Debate

```php
$debater1 = Chain::make(
    agent(instructions: 'Argue FOR the proposal'),
    PromptTemplate::from(
        'Proposal: {proposal}\nOpponent says: {opponent_view}\nYour rebuttal:'
    ),
    'pro_argument'
);

$debater2 = Chain::make(
    agent(instructions: 'Argue AGAINST the proposal'),
    PromptTemplate::from(
        'Proposal: {proposal}\nProponent says: {proponent_view}\nYour rebuttal:'
    ),
    'con_argument'
);

// Round 1
$pro1 = $debater1->run(['proposal' => 'Remote work should be mandatory']);
$con1 = $debater2->run([
    'proposal' => 'Remote work should be mandatory',
    'proponent_view' => $pro1
]);

// Round 2 (with memory of previous arguments)
$pro2 = $debater1->run([
    'proposal' => 'Remote work should be mandatory',
    'opponent_view' => $con1
]);
```

---

**Next:** [State Graphs](./04-state-graphs.md) →

