# Tutorial 3: Chains Composition & Pipelines (25 minutes)

In this tutorial, you'll chain multiple chains together to build complex processing pipelines.

## Prerequisites

✅ Complete [Tutorial 1: Your First Chain](./01-beginner-first-chain.md)  
✅ Complete [Tutorial 2: Memory & Conversation](./02-memory-and-conversation.md)  

## What You'll Learn

- Chain multiple chains with `then()`
- Understand key flow between chains
- Use `ChainFactory` for fluent building
- Build a content creation pipeline

## Step 1: Simple Chain Composition

Create `app/Examples/PipelineDemo.php`:

```php
<?php

namespace App\Examples;

use Nexus\\Workflow\Chains\Chain;
use Nexus\\Workflow\Prompts\PromptTemplate;
use function Laravel\Ai\agent;

class PipelineDemo
{
    public static function simpleChaining()
    {
        // 1️⃣ Step 1: Generate a blog title
        $titleChain = Chain::make(
            agent(instructions: 'Be creative and catchy.'),
            PromptTemplate::from('Create a blog title about: {topic}'),
            'title'  // Output key
        );

        // 2️⃣ Step 2: Create an outline from the title
        $outlineChain = Chain::make(
            agent(instructions: 'Be structured and logical.'),
            PromptTemplate::from('Create an outline for: {title}'),
            'outline'  // Output key
        );

        // 3️⃣ Chain them together
        $pipeline = $titleChain->then($outlineChain);

        // 4️⃣ Run! Input goes to first chain
        $result = $pipeline->run(['topic' => 'Machine Learning']);

        // Result contains: ['title' => '...', 'outline' => '...']
        echo "Title: " . $result['title'] . "\n";
        echo "Outline: " . $result['outline'] . "\n";
    }
}
```

Run it:

```bash
php artisan tinker
>>> \App\Examples\PipelineDemo::simpleChaining()
```

Output:
```
Title: "10 Machine Learning Concepts Every Developer Should Know"
Outline: "1. Supervised Learning
2. Unsupervised Learning
..."
```

## Step 2: Understanding Key Flow

The magic is in **key matching**:

```
Input: ['topic' => 'ML']
        ↓
   [titleChain]
   Prompt: "Create a blog title about: {topic}"
   Output key: 'title'
        ↓
   Result: ['title' => '10 ML Concepts...']
        ↓
   [outlineChain]
   Prompt: "Create an outline for: {title}"  ← {title} comes from previous output!
   Output key: 'outline'
        ↓
   Result: ['title' => '...', 'outline' => '...']
```

**Key point:** The output key from the first chain (`'title'`) becomes an input for the second chain (which has `{title}` in its template).

## Step 3: Three-Stage Pipeline

```php
class ContentPipeline
{
    public static function createBlogPost(string $topic): string
    {
        // Stage 1: Generate title
        $titleChain = Chain::make(
            agent(instructions: 'Create catchy titles.'),
            PromptTemplate::from('Create a blog title: {topic}'),
            'title'
        );

        // Stage 2: Create outline
        $outlineChain = Chain::make(
            agent(instructions: 'Create clear outlines.'),
            PromptTemplate::from('Outline for: {title}'),
            'outline'
        );

        // Stage 3: Write draft
        $draftChain = Chain::make(
            agent(instructions: 'Write engaging blog posts.'),
            PromptTemplate::from(<<<'PROMPT'
Write a blog post:

Title: {title}
Outline: {outline}

Write the full post now.
PROMPT),
            'draft'
        );

        // Chain them all
        $pipeline = $titleChain
            ->then($outlineChain)
            ->then($draftChain);

        // Run
        $result = $pipeline->run(['topic' => $topic]);

        return $result['draft'];
    }
}

// Use it
$post = ContentPipeline::createBlogPost('Blockchain Technology');
echo $post;
```

## Step 4: ChainFactory for Fluent Building

Use `ChainFactory` for a more concise syntax:

```php
use Nexus\\Workflow\Chains\ChainFactory;

class BlogFactory
{
    public static function build()
    {
        return ChainFactory::chain(
            agent(instructions: 'Be creative.'),
            PromptTemplate::from('Title: {topic}'),
            'title'
        )
        ->thenPrompt(
            agent(instructions: 'Be structured.'),
            PromptTemplate::from('Outline for: {title}'),
            'outline'
        )
        ->thenPrompt(
            agent(instructions: 'Write well.'),
            PromptTemplate::from('Draft post: {outline}'),
            'draft'
        )
        ->build();  // Returns a Chain
    }
}

// Use it
$chain = BlogFactory::build();
$result = $chain->run(['topic' => 'PHP Frameworks']);
echo $result['draft'];
```

## Step 5: Four-Stage Blog Pipeline

```php
class BlogAuthoring
{
    public static function execute(string $topic): array
    {
        return ChainFactory::chain(
            agent(instructions: 'Create engaging titles.'),
            PromptTemplate::from('Blog title: {topic}'),
            'title'
        )
        ->thenPrompt(
            agent(instructions: 'Create logical outlines.'),
            PromptTemplate::from('Outline: {title}'),
            'outline'
        )
        ->thenPrompt(
            agent(instructions: 'Write compelling drafts.'),
            PromptTemplate::from('Draft:\n{outline}'),
            'draft'
        )
        ->thenPrompt(
            agent(instructions: 'Edit and improve writing.'),
            PromptTemplate::from('Polish:\n{draft}'),
            'final'
        )
        ->build()
        ->run(['topic' => $topic]);
    }
}

// Run the full pipeline
$result = BlogAuthoring::execute('Artificial Intelligence Ethics');

echo "Title: " . $result['title'] . "\n";
echo "Outline: " . $result['outline'] . "\n";
echo "Final: " . $result['final'] . "\n";
```

## Step 6: Debugging Pipelines

See what happens at each stage:

```php
class DebugPipeline
{
    public static function run(array $inputs): void
    {
        $chain1 = Chain::make(
            agent(),
            PromptTemplate::from('Title: {input}'),
            'title'
        );

        $chain2 = Chain::make(
            agent(),
            PromptTemplate::from('Summary: {title}'),
            'summary'
        );

        $pipeline = $chain1->then($chain2);

        // At each stage, log the output
        $result1 = $chain1->run($inputs);
        echo "After chain 1:\n";
        echo json_encode($result1, JSON_PRETTY_PRINT) . "\n\n";

        $result2 = $chain2->run($result1);  // Pass result1 to chain2
        echo "After chain 2:\n";
        echo json_encode($result2, JSON_PRETTY_PRINT) . "\n";
    }
}
```

## Step 7: Error Handling in Pipelines

```php
class SafePipeline
{
    public static function run(): void
    {
        try {
            $result = ChainFactory::chain($agent1, $p1, 'step1')
                ->thenPrompt($agent2, $p2, 'step2')
                ->thenPrompt($agent3, $p3, 'step3')
                ->build()
                ->run(['input' => 'value']);

            echo "Pipeline succeeded:\n";
            print_r($result);
        } catch (Exception $e) {
            logger()->error('Pipeline failed at stage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            echo "Pipeline failed: " . $e->getMessage();
        }
    }
}
```

## Practical Example: Email Campaign Generator

```php
class EmailCampaignGenerator
{
    public static function generate(string $productName, string $targetAudience): array
    {
        return ChainFactory::chain(
            agent(instructions: 'Create compelling subject lines.'),
            PromptTemplate::from(
                'Subject line for {product} targeting {audience}:'
            ),
            'subject'
        )
        ->thenPrompt(
            agent(instructions: 'Write persuasive body copy.'),
            PromptTemplate::from(
                'Email body for:\n{subject}\nTarget: {audience}'
            ),
            'body'
        )
        ->thenPrompt(
            agent(instructions: 'Create strong CTAs.'),
            PromptTemplate::from(
                'CTA button text for:\n{body}'
            ),
            'cta'
        )
        ->build()
        ->run([
            'product' => $productName,
            'audience' => $targetAudience,
        ]);
    }
}

// Use it
$campaign = EmailCampaignGenerator::generate(
    'Laravel Training Course',
    'PHP developers with 2+ years experience'
);

echo "Subject: " . $campaign['subject'] . "\n";
echo "Body: " . $campaign['body'] . "\n";
echo "CTA: " . $campaign['cta'] . "\n";
```

## Exercises

### Exercise 1: Poem to Story
Build a pipeline that:
1. Generates a poem about a topic
2. Converts it to a short story

```php
// 👉 Your code here
```

### Exercise 2: Translation Pipeline
Build a pipeline that:
1. Translates English to Spanish
2. Improves the translation
3. Back-translates to English (for validation)

```php
// 👉 Your code here
```

### Exercise 3: Product Description Generator
Build a pipeline that:
1. Generates a catchy product title
2. Creates a description
3. Writes SEO-optimized meta tags

```php
// 👉 Your code here
```

## Common Issues

### Issue: "Key mismatch"

**Cause:** Output key from chain 1 doesn't match template variable in chain 2.

```php
// ❌ Wrong
$chain1 = Chain::make($agent, PromptTemplate::from('Title: {input}'), 'title');
$chain2 = Chain::make($agent, PromptTemplate::from('Summary: {description}'), 'summary');
// chain1 outputs 'title', but chain2 expects 'description'
```

**Solution:** Match the keys:

```php
// ✅ Correct
$chain1 = Chain::make($agent, PromptTemplate::from('Title: {input}'), 'title');
$chain2 = Chain::make($agent, PromptTemplate::from('Summary: {title}'), 'summary');
// Now they match!
```

### Issue: Data loss between stages

**Cause:** Only the last chain's output is returned.

**Solution:** Previous outputs are merged, so you can access them:

```php
$result = $chain1->then($chain2)->run(['input' => 'X']);
// $result has: ['title' => '...', 'summary' => '...']
// Both outputs are included
```

## Key Takeaways

✅ Chain multiple chains with `->then()`  
✅ Output key from one chain becomes input for the next  
✅ Use `ChainFactory` for fluent syntax  
✅ All outputs are preserved in the final result  
✅ Handle errors at the top level  

## Next Steps

→ Ready for complex workflows? Go to [Tutorial 4: State Graphs](./04-state-graphs-workflows.md)

Or continue exploring:
- [State Graphs](./04-state-graphs-workflows.md) — Conditional routing & branching
- [RAG](./05-advanced-rag.md) — Add retrieval to your chains

---

**Great progress!** 🚀

