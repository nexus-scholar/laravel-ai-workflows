# Chains & Composition

Guide for building and composing AI chains.

## Chain Basics

A chain connects an agent to a prompt template:

```php
use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

$chain = Chain::make(
    agent: agent(instructions: 'Be helpful'),
    promptTemplate: PromptTemplate::from('Q: {input}'),
    outputKey: 'answer'
);

$result = $chain->run(['input' => 'What is RAG?']);
```

## Chain Methods

**Running:**
```php
// Synchronous - waits for full response
$result = $chain->run(['input' => 'Hello']);

// Streaming - yields tokens as they arrive
foreach ($chain->stream(['input' => 'Hello']) as $token) {
    echo $token;
}
```

**Inspection:**
```php
$chain->inputKeys();   // ['input']
$chain->outputKey();   // 'answer'
```

**Enhancement:**
```php
$chain->withMemory($memory);          // Add memory
$chain->withRetriever($retriever);    // Add RAG
$chain->withProvider('anthropic');    // Override provider
$chain->withModel('claude-3');        // Override model
```

## Chain Composition

### Sequential Composition

Chain multiple chains with `then()`:

```php
$draft = Chain::make($agent1, $p1, 'draft');
$edit = Chain::make($agent2, $p2, 'edited');
$final = Chain::make($agent3, $p3, 'final');

$pipeline = $draft->then($edit)->then($final);

$result = $pipeline->run(['topic' => 'AI']);
// Result: ['draft' => '...', 'edited' => '...', 'final' => '...']
```

### Key Flow

Output keys from one chain become inputs to the next:

```
Input: {topic: 'AI'}
  ↓
[Chain1] outputKey='draft'
  ↓ Passes to Chain2
[Chain2] template uses {draft}
  ↓ outputKey='edited'
  ↓ Passes to Chain3
[Chain3] template uses {edited}
  ↓ outputKey='final'
Output: {final: '...'}
```

### Fluent Factory

Use `ChainFactory` for concise composition:

```php
use Nexus\\Workflow\Chains\ChainFactory;

$pipeline = ChainFactory::chain($agent1, $p1, 'step1')
    ->thenPrompt($agent2, $p2, 'step2')
    ->thenPrompt($agent3, $p3, 'step3')
    ->build();
```

## Advanced Patterns

### Conditional Chains

Route to different chains based on input:

```php
$simpleChain = Chain::make($agent, PromptTemplate::from('Simple: {input}'));
$complexChain = Chain::make($agent, PromptTemplate::from('Complex: {input}'));

$result = strlen($input) > 100 ? $complexChain->run([$input]) : $simpleChain->run([$input]);
```

### Retry Logic

Wrap chains for resilience:

```php
class RetryChain implements ChainContract
{
    public function __construct(
        private ChainContract $chain,
        private int $maxRetries = 3
    ) {}
    
    public function run(array $inputs): mixed
    {
        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            try {
                return $this->chain->run($inputs);
            } catch (Exception $e) {
                $attempt++;
                if ($attempt >= $this->maxRetries) throw $e;
                sleep(2 ** $attempt);  // Exponential backoff
            }
        }
    }
}

$safe = new RetryChain($chain, maxRetries: 3);
```

### Logging Decorator

Track chain execution:

```php
class LoggingChain implements ChainContract
{
    public function __construct(
        private ChainContract $chain,
        private string $name = 'Chain'
    ) {}
    
    public function run(array $inputs): mixed
    {
        logger()->info("[$this->name] Starting", ['inputs' => $inputs]);
        $start = microtime(true);
        
        try {
            $result = $this->chain->run($inputs);
            $duration = microtime(true) - $start;
            logger()->info("[$this->name] Completed", ['duration_ms' => round($duration * 1000)]);
            return $result;
        } catch (Exception $e) {
            logger()->error("[$this->name] Failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

### Caching

Avoid redundant calls:

```php
class CachingChain implements ChainContract
{
    public function __construct(
        private ChainContract $chain,
        private int $ttl = 3600
    ) {}
    
    public function run(array $inputs): mixed
    {
        $key = 'chain.' . md5(json_encode($inputs));
        return cache()->remember($key, $this->ttl, fn() => 
            $this->chain->run($inputs)
        );
    }
}
```

## Best Practices

1. **Clear Templates** — Make prompt variables obvious
2. **Named Keys** — Use descriptive input/output keys
3. **Compose Small** — Build small chains, compose them
4. **Test Early** — Test chains individually before composing
5. **Handle Errors** — Wrap in try-catch at top level
6. **Log Important Steps** — Track chain execution
7. **Cache Expensive Steps** — Avoid redundant LLM calls
8. **Validate Input** — Check required variables exist

## Common Patterns

**Draft → Edit → Publish:**
```php
$chain1 = Chain::make(..., 'draft');
$chain2 = Chain::make(..., 'edited');
$chain3 = Chain::make(..., 'published');
$pipeline = $chain1->then($chain2)->then($chain3);
```

**Summarize → Expand:**
```php
$chain1 = Chain::make(..., 'summary');
$chain2 = Chain::make(..., 'expanded');
$pipeline = $chain1->then($chain2);
```

**Translate → Verify:**
```php
$chain1 = Chain::make(..., 'translation');
$chain2 = Chain::make(..., 'verified');
$pipeline = $chain1->then($chain2);
```

See [Chains Guide](../../docs/03-chains-guide.md) for complete examples.

