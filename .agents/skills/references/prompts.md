# Prompt Templates & Engineering

Guide for working with prompts in laravel-ai-workflows.

## PromptTemplate Basics

Create templates with variable interpolation:

```php
use Nexus\\Workflow\Prompts\PromptTemplate;

$template = PromptTemplate::from(
    'You are a {role}.\n\nUser: {input}\n\nAssistant:'
);

$prompt = $template->format([
    'role' => 'helpful assistant',
    'input' => 'What is Laravel?'
]);
```

## Template Variables

Use `{variableName}` syntax for variables:

```php
$template = PromptTemplate::from(
    'Document: {document}\n\nAnalyze it for: {criteria}'
);

// Must provide all variables
$prompt = $template->format([
    'document' => 'The quick brown fox...',
    'criteria' => 'sentiment'
]);
```

## Partial Templates

Pre-fill some variables and keep others:

```php
$template = PromptTemplate::from(
    'From: {from}\nTo: {to}\nMessage: {message}'
);

// Pre-fill 'from'
$partial = $template->partial(['from' => 'Alice']);

// Still needs {to} and {message}
$complete = $partial->format(['to' => 'Bob', 'message' => 'Hi!']);
```

## Template Validation

Variables in template must be provided:

```php
$template = PromptTemplate::from('Q: {question}');

// ❌ This throws: missing 'question'
$template->format([]);

// ✅ This works
$template->format(['question' => 'What is PHP?']);

// ✅ Extra variables are ignored
$template->format(['question' => 'What is PHP?', 'extra' => 'ignored']);
```

## Using with Chains

Prompts are the core of chains:

```php
use Nexus\\Workflow\Chains\Chain;
use function Laravel\Ai\agent;

$prompt = PromptTemplate::from(
    'You are a {expertise} expert.\nQuestion: {input}'
);

$chain = Chain::make(
    agent(instructions: 'Be accurate'),
    $prompt,
    outputKey: 'answer'
);

$result = $chain->run([
    'expertise' => 'Machine Learning',
    'input' => 'What is a neural network?'
]);
```

## Advanced Patterns

### Few-Shot Prompting

Include examples in the template:

```php
$template = PromptTemplate::from(
    'Classify sentiment.\n\n'
    .'Example: "Great product!" → positive\n'
    .'Example: "Terrible!" → negative\n\n'
    .'Text: {text}\nClassification:'
);
```

### Chain-of-Thought

Guide the AI through reasoning:

```php
$template = PromptTemplate::from(
    'Question: {question}\n\n'
    .'Think step by step:\n'
    .'1. First understand what is being asked\n'
    .'2. Break it into parts\n'
    .'3. Solve each part\n'
    .'4. Combine the answers\n\n'
    .'Answer:'
);
```

### RAG with Prompts

Inject retrieved context:

```php
$template = PromptTemplate::from(
    'Context:\n{context}\n\n'
    .'User Question: {input}\n\n'
    .'Based only on the context above, answer:'
);

$chain = Chain::make($agent, $template)
    ->withRetriever($retriever);  // Provides {context}
```

## Memory Integration

Include conversation history:

```php
$template = PromptTemplate::from(
    'Chat History:\n{history}\n\n'
    .'New Message: {input}\n\n'
    .'Response:'
);

$chain = Chain::make($agent, $template)
    ->withMemory($memory);  // Provides {history}
```

## Best Practices

1. **Clear Instructions** — Be explicit about what you want
2. **Separate Concerns** — Keep templates simple, one purpose each
3. **Variable Naming** — Use descriptive names like `{question}`, not `{q}`
4. **Context Injection** — Use `{context}`, `{history}`, `{input}` consistently
5. **Error Handling** — Handle missing variables gracefully
6. **Testing** — Test templates with various inputs before production

## Common Template Patterns

**Simple Q&A:**
```php
PromptTemplate::from('Q: {input}')
```

**Role-Based:**
```php
PromptTemplate::from('You are a {role}.\n\n{input}')
```

**RAG:**
```php
PromptTemplate::from('Context:\n{context}\n\n{input}')
```

**Conversation:**
```php
PromptTemplate::from('History:\n{history}\n\n{input}')
```

**Analysis:**
```php
PromptTemplate::from('Analyze {document} for {criteria}')
```

See [Chains Guide](../../docs/03-chains-guide.md) for more examples.
