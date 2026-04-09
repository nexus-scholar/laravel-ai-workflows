# Memory Systems

Memory allows chains and graphs to maintain context across multiple interactions. This guide covers all memory strategies and when to use them.

## Memory Types

### 1. In-Memory Conversation

Stores history in PHP memory. Best for single-session workflows.

```php
use Nexus\\Workflow\Memory\InMemoryConversation;

$memory = new InMemoryConversation();
$memory->add('user', 'What is PHP?');
$memory->add('assistant', 'PHP is a server-side language...');

// Get all messages
$messages = $memory->messages();
// [
//   ['role' => 'user', 'content' => 'What is PHP?'],
//   ['role' => 'assistant', 'content' => 'PHP is a server-side language...']
// ]

// Get as formatted string for injection
$history = $memory->asString();
// "user: What is PHP?\nassistant: PHP is a server-side language..."

// Clear memory
$memory->clear();
```

### 2. Cache Conversation Memory

Stores history in Laravel cache (Redis, File, etc). Best for multi-user scenarios.

```php
use Nexus\\Workflow\Memory\CacheConversationMemory;

$memory = new CacheConversationMemory(
    key: 'chat.user.123',     // Unique per conversation
    ttl: 3600                  // 1 hour expiration
);

$memory->add('user', 'Hello');
$memory->add('assistant', 'Hi there!');

// Later, retrieve the same conversation
$retrieved = new CacheConversationMemory(
    key: 'chat.user.123',
    ttl: 3600
);
echo count($retrieved->messages());  // 2 messages (persisted!)
```

### 3. Summary Memory

Keeps recent messages, summarizes older ones to reduce tokens.

```php
use Nexus\\Workflow\Memory\SummaryMemory;
use function Laravel\Ai\agent;

$memory = new SummaryMemory(
    maxMessages: 10,
    agent: agent(instructions: 'Summarize concisely'),
    // Optional
    summarizerPrompt: 'Condense these messages into 1-2 sentences: {messages}'
);

// Add many messages
for ($i = 0; $i < 15; $i++) {
    $memory->add('user', "Message $i");
    $memory->add('assistant', "Response to message $i");
}

// Only last 10 kept; older ones summarized
$messages = $memory->messages();
echo count($messages);  // Always ~10, not 30
```

## Using Memory with Chains

### Basic Usage

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;
use Nexus\\Workflow\Memory\InMemoryConversation;
use function Laravel\Ai\agent;

$memory = new InMemoryConversation();

$prompt = PromptTemplate::from(<<<'PROMPT'
Previous messages:
{history}

User: {input}
PROMPT);

$chain = Chain::make(
    agent(),
    $prompt
)->withMemory($memory);

// First message
$result1 = $chain->run(['input' => 'What is Laravel?']);
echo $result1;  // "Laravel is a PHP framework..."

// Update memory
$memory->add('user', 'What is Laravel?');
$memory->add('assistant', (string)$result1);

// Second message (sees history)
$result2 = $chain->run(['input' => 'How is it different from Symfony?']);
// Agent sees previous exchange and can reference it
```

### Automatic Memory Updates

For convenience, create a wrapper:

```php
class ConversationChain
{
    private Memory $memory;
    private Chain $chain;

    public function __construct(Memory $memory, Chain $chain)
    {
        $this->memory = $memory;
        $this->chain = $chain;
    }

    public function ask(string $question): string
    {
        $result = $this->chain->run(['input' => $question]);
        $this->memory->add('user', $question);
        $this->memory->add('assistant', (string)$result);
        return $result;
    }
}

$memory = new InMemoryConversation();
$chain = Chain::make($agent, $prompt)->withMemory($memory);
$conversation = new ConversationChain($memory, $chain);

echo $conversation->ask('Hello, how are you?');     // Added to memory
echo $conversation->ask('Tell me a joke.');          // Sees previous exchange
```

## Using Memory with State Graphs

Memory can be part of your state:

```php
use Nexus\\Workflow\Graph\State;
use Nexus\\Workflow\Graph\StateGraph;
use Nexus\\Workflow\Memory\InMemoryConversation;

final class ConversationState extends State
{
    public function __construct(
        public InMemoryConversation $memory = new InMemoryConversation(),
        public string $currentInput = '',
        public string $lastResponse = '',
    ) {}

    public function toArray(): array
    {
        return [
            'messages' => $this->memory->messages(),
            'currentInput' => $this->currentInput,
            'lastResponse' => $this->lastResponse,
        ];
    }

    public static function fromArray(array $data): static
    {
        $memory = new InMemoryConversation();
        foreach ($data['messages'] ?? [] as $msg) {
            $memory->add($msg['role'], $msg['content']);
        }

        return new self(
            memory: $memory,
            currentInput: $data['currentInput'] ?? '',
            lastResponse: $data['lastResponse'] ?? '',
        );
    }
}

$graph = new StateGraph();

$graph->addNode('process', fn(ConversationState $s) => {
    $response = callAgent($s->currentInput, $s->memory->asString());
    $s->memory->add('user', $s->currentInput);
    $s->memory->add('assistant', $response);
    
    return $s->with(['lastResponse' => $response]);
});

$graph->setEntryPoint('process');
$graph->addEdge('process', StateGraph::END);

$compiled = $graph->compile();
$state = new ConversationState(currentInput: 'Hello');
$result = $compiled->invoke($state);

echo $result->lastResponse;
```

## Multi-Turn Conversations

### Pattern 1: Stateful Service

```php
class ChatbotService
{
    private array $conversations = [];

    public function chat(string $userId, string $message): string
    {
        // Get or create memory for this user
        if (!isset($this->conversations[$userId])) {
            $this->conversations[$userId] = new CacheConversationMemory(
                key: "chat.$userId",
                ttl: 86400  // 24 hours
            );
        }

        $memory = $this->conversations[$userId];
        
        $chain = Chain::make($agent, $prompt)->withMemory($memory);
        $response = $chain->run(['input' => $message]);
        
        // Save to memory
        $memory->add('user', $message);
        $memory->add('assistant', (string)$response);

        return $response;
    }
}

// Use it
$bot = new ChatbotService();
echo $bot->chat('user123', 'What is Laravel?');      // First message
echo $bot->chat('user123', 'How do I install it?');  // Sees history
echo $bot->chat('user456', 'What is Laravel?');      // Different user, fresh
```

### Pattern 2: Web Request Handler

```php
// In a Laravel controller

class ChatController
{
    public function send(Request $request)
    {
        $userId = $request->user()->id;
        $message = $request->input('message');

        $memory = new CacheConversationMemory(
            key: "chat.$userId",
            ttl: 86400
        );

        $chain = Chain::make($agent, $prompt)->withMemory($memory);
        $response = $chain->run(['input' => $message]);

        $memory->add('user', $message);
        $memory->add('assistant', (string)$response);

        return response()->json([
            'message' => $response,
            'history' => $memory->messages()
        ]);
    }
}
```

## Memory Strategies Comparison

| Strategy | Persistence | Scale | Cost | Ideal For |
|----------|-------------|-------|------|-----------|
| In-Memory | None | Single | Free | Dev/testing, single session |
| Cache | Yes (configurable) | Multi-user | Low | Multi-user apps, production |
| Summary | Yes | Large conversations | Medium | Long-running conversations |

## Advanced: Custom Memory Implementation

Extend the interface for custom behavior:

```php
use Nexus\\Workflow\Contracts\Memory;

class DatabaseMemory implements Memory
{
    public function __construct(
        private int $conversationId,
        private PDO $db,
    ) {}

    public function add(string $role, string $content): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (conversation_id, role, content, created_at) 
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$this->conversationId, $role, $content]);
    }

    public function messages(): array
    {
        $stmt = $this->db->prepare(
            'SELECT role, content FROM messages WHERE conversation_id = ? 
             ORDER BY created_at ASC'
        );
        $stmt->execute([$this->conversationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clear(): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM messages WHERE conversation_id = ?'
        );
        $stmt->execute([$this->conversationId]);
    }

    public function asString(): string
    {
        return implode("\n", array_map(
            fn($msg) => "{$msg['role']}: {$msg['content']}",
            $this->messages()
        ));
    }
}

// Use it
$memory = new DatabaseMemory(conversationId: 42, db: $pdo);
$chain = Chain::make($agent, $prompt)->withMemory($memory);
```

## Memory + RAG + Chains Pattern

Combine memory and retrieval:

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Memory\CacheConversationMemory;
use Nexus\\Workflow\Retrieval\VectorStoreRetriever;

$memory = new CacheConversationMemory(key: "user.$userId");

$prompt = PromptTemplate::from(<<<'PROMPT'
Documents:
{context}

Chat history:
{history}

Question: {input}

Answer based on the documents and conversation history.
PROMPT);

$chain = Chain::make($agent, $prompt)
    ->withMemory($memory)
    ->withRetriever(new VectorStoreRetriever($vectorStore), topK: 5);

// Each call:
// 1. Retrieves relevant docs
// 2. Includes chat history
// 3. Answers question
// 4. Updates memory

$answer = $chain->run(['input' => 'How do I deploy?']);
$memory->add('user', 'How do I deploy?');
$memory->add('assistant', $answer);
```

---

**Next:** [Retrieval & RAG](./06-retrieval-rag.md) →

