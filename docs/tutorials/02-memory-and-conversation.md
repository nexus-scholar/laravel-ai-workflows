# Tutorial 2: Memory & Conversation (20 minutes)

In this tutorial, you'll add conversation memory to your chains, enabling multi-turn conversations.

## Prerequisites

✅ Complete [Tutorial 1: Your First Chain](./01-beginner-first-chain.md)  
✅ Understand basic chains  

## What You'll Learn

- Add conversation memory to chains
- Build a multi-turn chatbot
- Persist memory across requests
- Handle chat history context

## Step 1: Simple In-Memory Chat

Create `app/Examples/MemoryChatbot.php`:

```php
<?php

namespace App\Examples;

use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Memory\InMemoryConversation;
use Nexus\AiChain\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

class MemoryChatbot
{
    public static function chat()
    {
        // 1️⃣ Create memory
        $memory = new InMemoryConversation();

        // 2️⃣ Create prompt that includes history
        $prompt = PromptTemplate::from(<<<'PROMPT'
Previous messages:
{history}

User message: {input}

Be helpful and remember the context from previous messages.
PROMPT);

        // 3️⃣ Create agent & chain
        $agent = agent(instructions: 'Be friendly and helpful.');
        $chain = Chain::make($agent, $prompt)
            ->withMemory($memory);

        // 4️⃣ Multi-turn conversation
        $messages = [
            'My name is Alice.',
            'What did I just tell you?',
            'Tell me a joke about coding.',
        ];

        foreach ($messages as $input) {
            echo "User: $input\n";
            
            $response = $chain->run(['input' => $input]);
            echo "Bot: $response\n\n";
            
            // Add to memory
            $memory->add('user', $input);
            $memory->add('assistant', (string)$response);
        }

        // View memory
        echo "=== Chat History ===\n";
        echo $memory->asString();
    }
}
```

Run it:

```bash
php artisan tinker
>>> \App\Examples\MemoryChatbot::chat()
```

Output shows:
```
User: My name is Alice.
Bot: Nice to meet you, Alice! How can I help you today?

User: What did I just tell you?
Bot: You told me your name is Alice.  ← See? It remembers!

User: Tell me a joke about coding.
Bot: Why do programmers prefer dark mode? Because light attracts bugs!
```

## Step 2: Understanding Memory

### What Happens

```php
$memory = new InMemoryConversation();

// First turn
$memory->add('user', 'Hello');
$memory->add('assistant', 'Hi there!');

// When chain runs with {history}, it includes:
// "user: Hello\nassistant: Hi there!"

// Second turn (AI sees history)
$memory->add('user', 'How are you?');
$memory->add('assistant', 'I am doing well!');

// Now {history} includes all 4 messages
```

### Memory as String

Memory can be formatted for injection:

```php
$memory->add('user', 'What is PHP?');
$memory->add('assistant', 'PHP is a language...');

echo $memory->asString();
// Output:
// user: What is PHP?
// assistant: PHP is a language...
```

## Step 3: Persistent Memory (Cache)

For production, persist memory across requests:

```php
use Nexus\AiChain\Memory\CacheConversationMemory;

class PersistentChatbot
{
    public static function chat(string $userId, string $input)
    {
        // 1️⃣ Get or create memory for this user
        $memory = new CacheConversationMemory(
            key: "chat.$userId",     // Unique per user
            ttl: 86400               // 24 hours
        );

        // 2️⃣ Chain with memory
        $prompt = PromptTemplate::from(<<<'PROMPT'
Chat history:
{history}

User: {input}
PROMPT);

        $chain = Chain::make(agent(), $prompt)
            ->withMemory($memory);

        // 3️⃣ Run (AI sees all previous messages)
        $response = $chain->run(['input' => $input]);

        // 4️⃣ Save to memory
        $memory->add('user', $input);
        $memory->add('assistant', (string)$response);

        return $response;
    }
}

// Use it
echo PersistentChatbot::chat('user123', 'Hello');         // First message
echo PersistentChatbot::chat('user123', 'How are you?');  // Sees history!
echo PersistentChatbot::chat('user456', 'Hello');         // Fresh conversation
```

## Step 4: Build a Web Chatbot

Create a Laravel controller:

```bash
php artisan make:controller ChatController
```

Edit `app/Http/Controllers/ChatController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Memory\CacheConversationMemory;
use Nexus\AiChain\Prompts\PromptTemplate;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $userId = $request->user()?->id ?? 'guest';
        $userMessage = $request->input('message');

        // Get persistent memory for this user
        $memory = new CacheConversationMemory(
            key: "chat.$userId",
            ttl: 86400
        );

        // Create chain
        $chain = Chain::make(
            agent(instructions: 'You are a helpful assistant.'),
            PromptTemplate::from('Chat:\n{history}\n\nUser: {input}')
        )->withMemory($memory);

        // Get response
        $response = $chain->run(['input' => $userMessage]);

        // Save to memory
        $memory->add('user', $userMessage);
        $memory->add('assistant', (string)$response);

        return response()->json([
            'message' => $response,
            'history' => $memory->messages(),
        ]);
    }

    public function history(Request $request)
    {
        $userId = $request->user()?->id ?? 'guest';

        $memory = new CacheConversationMemory(
            key: "chat.$userId",
            ttl: 86400
        );

        return response()->json([
            'messages' => $memory->messages(),
        ]);
    }
}
```

Add routes in `routes/api.php`:

```php
Route::middleware('auth:api')->group(function () {
    Route::post('/chat', [ChatController::class, 'send']);
    Route::get('/chat/history', [ChatController::class, 'history']);
});
```

## Step 5: Token Optimization with Summary Memory

For long conversations, summarize old messages:

```php
use Nexus\AiChain\Memory\SummaryMemory;
use function Laravel\Ai\agent;

$memory = new SummaryMemory(
    maxMessages: 10,  // Keep last 10 messages only
    agent: agent(instructions: 'Summarize concisely')
);

// Add many messages
for ($i = 0; $i < 20; $i++) {
    $memory->add('user', "Message $i");
    $memory->add('assistant', "Response to message $i");
}

// Only last 10 messages kept; older ones summarized
$messages = $memory->messages();
echo count($messages);  // ~10 messages (not 40!)

// First message will be a summary of messages 1-10
echo $messages[0]['content'];  
// "Summary: The user asked about..., and we discussed..."
```

## Step 6: Clear Memory

Remove all messages:

```php
$memory->clear();
$memory->messages();  // Returns []
```

## Practical Example: Book Club Bot

```php
class BookClubBot
{
    private string $bookTitle = 'The Great Gatsby';
    private string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function chat(string $message): string
    {
        $memory = new CacheConversationMemory(
            key: "book_club.{$this->userId}",
            ttl: 604800  // 1 week
        );

        $prompt = PromptTemplate::from(<<<'PROMPT'
You are discussing "{book_title}".

Previous discussion:
{history}

User: {input}

Continue the discussion naturally.
PROMPT);

        $chain = Chain::make(
            agent(instructions: 'You are a literary expert.'),
            $prompt
        )->withMemory($memory);

        $response = $chain->run([
            'input' => $message,
            'book_title' => $this->bookTitle
        ]);

        $memory->add('user', $message);
        $memory->add('assistant', (string)$response);

        return $response;
    }
}

// Use it
$bot = new BookClubBot('alice_123');
echo $bot->chat('What do you think of Gatsby?');
echo $bot->chat('Is Daisy a sympathetic character?');  // Bot remembers previous discussion
```

## Exercises

### Exercise 1: Task Tracker
Build a bot that remembers tasks the user mentioned:

```php
// Create a memory that tracks mentioned tasks
// Example: User says "I need to fix bug #123"
// Later: User says "What tasks have I mentioned?"
// Bot recalls: "You mentioned fixing bug #123"
```

### Exercise 2: Language Learning Bot
Create a bot that remembers vocabulary:

```php
// User: "The word 'gato' means cat in Spanish"
// Later: "What does 'gato' mean?"
// Bot: "It means cat, as you taught me earlier"
```

### Exercise 3: Custom Memory Format
Create a custom memory class that stores to a database:

```php
class DatabaseMemory implements Memory
{
    // Implement: add(), messages(), clear(), asString()
    // Use a database instead of cache
}
```

## Common Issues

### Issue: Memory not persisting

**Cause:** Using `InMemoryConversation` which doesn't persist across requests.

**Solution:** Use `CacheConversationMemory`:

```php
// ❌ Don't do this in web controllers
$memory = new InMemoryConversation();

// ✅ Do this instead
$memory = new CacheConversationMemory(key: "user.$userId");
```

### Issue: Memory too long

**Cause:** Conversation grows indefinitely, consuming tokens.

**Solution:** Use `SummaryMemory`:

```php
$memory = new SummaryMemory(maxMessages: 10, agent: $agent);
```

## Key Takeaways

✅ Memory stores conversation history  
✅ Templates use `{history}` to include it  
✅ `InMemoryConversation` is for single sessions  
✅ `CacheConversationMemory` persists across requests  
✅ `SummaryMemory` reduces tokens for long conversations  

## Next Steps

→ Ready for the next level? Go to [Tutorial 3: Chains Composition](./03-chains-composition.md)

Or skip to [Tutorial 4: State Graphs](./04-state-graphs-workflows.md) if you want to explore workflows.

---

**You're doing great!** 🎉

