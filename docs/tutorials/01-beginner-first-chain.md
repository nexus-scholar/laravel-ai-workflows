# Tutorial 1: Your First Chain (15 minutes)

In this tutorial, you'll create and run your first chain. No prior knowledge of laravel-ai-workflows needed!

## Prerequisites

✅ PHP 8.3+  
✅ Laravel project with laravel-ai-workflows installed  
✅ AI provider configured (OpenAI, Anthropic, etc.)  

## What You'll Learn

- Create a chain from an agent and prompt
- Run it synchronously
- Stream responses in real-time
- Understand input/output keys

## Step 1: Create Your First Chain

Create a new file `app/Examples/FirstChain.php`:

```php
<?php

namespace App\Examples;

use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

class FirstChain
{
    public static function run()
    {
        // 1️⃣ Create a prompt template
        $prompt = PromptTemplate::from(
            'You are a helpful assistant. Answer this: {question}'
        );

        // 2️⃣ Create an agent (powered by laravel/ai)
        $agent = agent(
            instructions: 'Be concise and friendly.'
        );

        // 3️⃣ Create a chain linking them
        $chain = Chain::make(
            agent: $agent,
            promptTemplate: $prompt,
            outputKey: 'answer'
        );

        // 4️⃣ Run it!
        $result = $chain->run([
            'question' => 'What is Laravel?'
        ]);

        return $result;
    }
}
```

## Step 2: Run It in Tinker

```bash
php artisan tinker
```

Then:

```php
>>> \App\Examples\FirstChain::run()
# Output: "Laravel is a powerful PHP framework..."
```

🎉 **You just created and ran your first chain!**

## Understanding the Pieces

### The Prompt

```php
$prompt = PromptTemplate::from(
    'You are a helpful assistant. Answer this: {question}'
);
```

This is a template with a `{question}` placeholder. When you run the chain, you provide the actual value.

### The Agent

```php
$agent = agent(
    instructions: 'Be concise and friendly.'
);
```

The agent is the AI that will process the prompt. The `instructions` tell it how to behave.

### The Chain

```php
$chain = Chain::make(
    agent: $agent,
    promptTemplate: $prompt,
    outputKey: 'answer'
);
```

The chain ties everything together:
- `$agent` — the AI
- `$promptTemplate` — the template with placeholders
- `$outputKey` — the name of the output (useful when chaining multiple chains)

### Running It

```php
$result = $chain->run([
    'question' => 'What is Laravel?'
]);
```

The input must provide all template variables (`{question}` in this case).

## Step 3: Try Streaming

Instead of waiting for the full response, stream it in real-time:

```php
class FirstChain
{
    public static function stream()
    {
        $prompt = PromptTemplate::from(
            'You are a helpful assistant. Answer this: {question}'
        );
        $agent = agent(instructions: 'Be concise and friendly.');
        $chain = Chain::make($agent, $prompt, 'answer');

        // Stream instead of run()
        foreach ($chain->stream(['question' => 'What is Laravel?']) as $token) {
            echo $token;  // Print each token as it arrives
            flush();      // Force output immediately
        }
    }
}
```

Run it:

```bash
php artisan tinker
>>> \App\Examples\FirstChain::stream()
# Output appears token by token in real-time!
```

## Step 4: Create a Command

Instead of using Tinker, create a reusable command:

```bash
php artisan make:command FirstChainCommand
```

Edit `app/Console/Commands/FirstChainCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Examples\FirstChain;
use Illuminate\Console\Command;

class FirstChainCommand extends Command
{
    protected $signature = 'example:first-chain';
    protected $description = 'Run the first chain example';

    public function handle()
    {
        $this->info('Starting chain...');
        $result = FirstChain::run();
        $this->info("Result: $result");
    }
}
```

Now run it:

```bash
php artisan example:first-chain
```

## Step 5: Explore Input & Output Keys

Understand the input/output system:

```php
$chain = Chain::make($agent, $prompt, 'answer');

// Get what inputs this chain expects
$inputs = $chain->inputKeys();
echo implode(', ', $inputs);  // "question"

// Get the output key
echo $chain->outputKey();  // "answer"

// When you run it, the output is keyed
$result = $chain->run(['question' => 'What is PHP?']);
// Result is actually: ['answer' => 'PHP is a language...']
// But Chain provides __toString() for convenience
```

## Step 6: Multiple Variables

Chains support multiple template variables:

```php
$prompt = PromptTemplate::from(<<<'PROMPT'
You are an expert in {expertise}.

User: {name}
Question: {question}

Answer it helpfully.
PROMPT);

$chain = Chain::make($agent, $prompt, 'response');

$result = $chain->run([
    'expertise' => 'Machine Learning',
    'name' => 'Alice',
    'question' => 'What is a neural network?'
]);
```

## Common Issues & Solutions

### Issue: "Method not found"

**Cause:** Chain method doesn't exist.

**Solution:** Check the API reference or ensure you're using correct method names (e.g., `->run()` not `->execute()`).

### Issue: "Missing template variable"

**Cause:** Prompt expects a variable you didn't provide.

```php
$prompt = PromptTemplate::from('Q: {question}');
$chain->run([]);  // ❌ Missing 'question'
```

**Solution:** Provide all variables:

```php
$chain->run(['question' => 'Hello']);  // ✅ Works
```

### Issue: "Provider not configured"

**Cause:** No AI provider set up or API key missing.

**Solution:** Check your `.env` file and ensure API key is set:

```bash
OPENAI_API_KEY=sk-...
```

## Exercises

### Exercise 1: Q&A Bot
Create a chain that answers questions about your favorite topic.

```php
// 👉 Your code here
```

### Exercise 2: Translation Chain
Create a chain that translates English to French.

```php
$prompt = PromptTemplate::from(
    'Translate to French: {text}'
);

$chain = Chain::make($agent, $prompt, 'translation');
$result = $chain->run(['text' => 'Hello, world!']);
```

### Exercise 3: Sentiment Analysis
Create a chain that analyzes sentiment of text.

```php
// 👉 Your code here
```

## Next Steps

✅ You now understand basic chains!

→ Ready for the next level? Go to [Tutorial 2: Memory & Conversation](./02-memory-and-conversation.md)

Or explore other tutorials:
- [Chains Composition](./03-chains-composition.md) — Chain multiple chains
- [State Graphs](./04-state-graphs-workflows.md) — Complex workflows

---

**Happy coding!** 🚀

