# Getting Started with Laravel AI Chain

This guide walks you through installing, configuring, and running your first chain in just minutes.

## Installation

### 1. Add the Package

```bash
composer require nexus/laravel-ai-chain
```

### 2. Publish Configuration (Optional)

If you're using Laravel, publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-chain-config
```

This creates `config/ai-chain.php` where you can customize:

- Memory backends (cache, in-memory)
- Retriever configuration
- Graph execution settings
- Queue behavior

### 3. Verify Installation

Check that the service provider is auto-discovered:

```bash
php artisan tinker
>>> app('ai-chain') // Should return AiChainManager instance
```

## Your First Chain

### Prerequisites

You need an AI provider configured. The package uses `laravel/ai`, so follow its setup:

```bash
# For OpenAI
composer require laravel-ai-provider/openai
```

Set your API key:

```bash
# .env
OPENAI_API_KEY=sk-...
```

### Create a Simple Chain

Create a file `app/Examples/FirstChain.php`:

```php
<?php

namespace App\Examples;

use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Prompts\PromptTemplate;

use function Laravel\Ai\agent;

class FirstChain
{
    public static function run()
    {
        // Step 1: Create a prompt template
        $prompt = PromptTemplate::from(
            'You are a helpful assistant. Answer: {question}'
        );

        // Step 2: Create an agent
        $myAgent = agent(
            instructions: 'Be concise and friendly.'
        );

        // Step 3: Create a chain
        $chain = Chain::make(
            agent: $myAgent,
            promptTemplate: $prompt,
            outputKey: 'answer'
        );

        // Step 4: Run it!
        $result = $chain->run([
            'question' => 'What is machine learning?'
        ]);

        return $result;
    }
}
```

### Run from Tinker

```bash
php artisan tinker
>>> \App\Examples\FirstChain::run()
# Output: "Machine learning is a subset of AI where..."
```

### Or from a Command

Create `app/Console/Commands/ExampleChainCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Examples\FirstChain;
use Illuminate\Console\Command;

class ExampleChainCommand extends Command
{
    protected $signature = 'example:first-chain';
    protected $description = 'Run the first example chain';

    public function handle()
    {
        $result = FirstChain::run();
        $this->info("Result: {$result}");
    }
}
```

Then:

```bash
php artisan example:first-chain
```

## Understanding the Components

### 1. **Prompt Template**

Templates use `{variable}` syntax for interpolation:

```php
$prompt = PromptTemplate::from(
    'Analyze this text: {text}'
);
```

### 2. **Agent**

Agents are powered by `laravel/ai`:

```php
use function Laravel\Ai\agent;

$agent = agent(
    instructions: 'You are an expert...',
    model: 'gpt-4',  // Optional override
);
```

### 3. **Chain**

A chain connects an agent to a prompt template:

```php
$chain = Chain::make(
    agent: $agent,
    promptTemplate: $prompt,
    outputKey: 'output'  // Key for the result
);
```

### 4. **Execution**

Chains support two execution modes:

```php
// Synchronous (returns full result)
$result = $chain->run(['input' => 'Hello']);

// Streaming (yields tokens as they arrive)
foreach ($chain->stream(['input' => 'Hello']) as $token) {
    echo $token;  // Print real-time
}
```

## Composing Chains

You can chain multiple chains together:

```php
$draft = Chain::make($agent1, $promptTemplate1, 'draft');
$refine = Chain::make($agent2, $promptTemplate2, 'refined');

// Compose: draft.output → refine.input
$workflow = $draft->then($refine);

$result = $workflow->run([
    'input' => 'Write a poem about PHP'
]);

echo $result;  // The refined poem
```

## Next Steps

- 📖 Read [Core Concepts](./02-core-concepts.md) to understand chains vs. state graphs
- 🔗 Explore [Chains Guide](./03-chains-guide.md) for advanced chain patterns
- 💾 Add [Memory](./05-memory-systems.md) for conversation context
- 🔍 Enable [Retrieval](./06-retrieval-rag.md) for RAG workflows
- 📚 Work through the [Tutorials](./tutorials/) for structured learning

## Common Issues

### Issue: "Provider not configured"

**Cause:** Missing API key or provider setup.

**Solution:**
```bash
# Check .env
cat .env | grep OPENAI_API_KEY

# Or set it
export OPENAI_API_KEY=sk-...
```

### Issue: "Template variable not provided"

**Cause:** Missing key in input array.

**Solution:**
```php
// Prompt expects {input}
$prompt = PromptTemplate::from('Question: {input}');

// Must provide 'input' key
$result = $chain->run(['input' => 'What is PHP?']);
```

### Issue: "Method stream() not yielding"

**Cause:** Using agent that doesn't support streaming.

**Solution:** Check if your provider supports streaming. Some providers require explicit configuration.

## Tips & Best Practices

1. **Use Type Hints** — Specify input/output keys explicitly for clarity
2. **Keep Prompts DRY** — Extract repeated prompts to constants or methods
3. **Compose Early** — Break complex workflows into smaller chains
4. **Test Templates** — Verify interpolation with dummy inputs
5. **Handle Errors** — Wrap `run()` calls in try-catch for production

## Full Example

Here's a complete example that brings it all together:

```php
<?php

use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

// Create templates
$writer = PromptTemplate::from(
    'Write a short blog post title about: {topic}'
);

$summarizer = PromptTemplate::from(
    'Summarize this in one sentence: {title}'
);

// Create agents
$agent1 = agent(instructions: 'Be creative.');
$agent2 = agent(instructions: 'Be concise.');

// Build chain
$chain = Chain::make($agent1, $writer, 'title')
    ->then(Chain::make($agent2, $summarizer, 'summary'));

// Run it
$inputs = ['topic' => 'Artificial Intelligence'];
$result = $chain->run($inputs);

echo "Final Output:\n";
echo $result;
```

---

**Next:** Learn about [Core Concepts](./02-core-concepts.md) →

