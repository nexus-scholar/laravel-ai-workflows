# Tutorial 4: State Graphs & Workflows (30 minutes)

In this tutorial, you'll master deterministic workflows with conditional routing and complex branching.

## Prerequisites

✅ Complete [Tutorial 3: Chains Composition](./03-chains-composition.md)  

## What You'll Learn

- Create custom state classes
- Build graphs with nodes and edges
- Use conditional routing (if/then/else)
- Implement complex workflows

## Step 1: Your First State Graph

Create `app/Examples/StateGraphDemo.php`:

```php
<?php

namespace App\Examples;

use Nexus\\Workflow\Graph\State;
use Nexus\\Workflow\Graph\StateGraph;

// 1️⃣ Define a custom state class
final class DocumentState extends State
{
    public function __construct(
        public string $text = '',
        public array $entities = [],
        public string $category = '',
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'entities' => $this->entities,
            'category' => $this->category,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            text: $data['text'] ?? '',
            entities: $data['entities'] ?? [],
            category: $data['category'] ?? '',
        );
    }
}

class StateGraphDemo
{
    public static function run()
    {
        // 2️⃣ Create a graph
        $graph = new StateGraph();

        // 3️⃣ Add nodes (processing steps)
        $graph->addNode('extract', fn(DocumentState $s) => {
            // Simple entity extraction (in reality, use AI)
            $entities = ['AI', 'Machine Learning'];
            return $s->with(['entities' => $entities]);
        });

        $graph->addNode('categorize', fn(DocumentState $s) => {
            // Simple categorization
            $category = count($s->entities) > 0 ? 'tech' : 'other';
            return $s->with(['category' => $category]);
        });

        // 4️⃣ Set entry point
        $graph->setEntryPoint('extract');

        // 5️⃣ Add edges (connections)
        $graph->addEdge('extract', 'categorize');
        $graph->addEdge('categorize', StateGraph::END);

        // 6️⃣ Compile and run
        $compiled = $graph->compile();
        $result = $compiled->invoke(new DocumentState(
            text: 'AI is transforming the world with machine learning.'
        ));

        echo "Text: " . $result->text . "\n";
        echo "Entities: " . implode(', ', $result->entities) . "\n";
        echo "Category: " . $result->category . "\n";
    }
}
```

Run it:

```bash
php artisan tinker
>>> \App\Examples\StateGraphDemo::run()
```

Output:
```
Text: AI is transforming the world with machine learning.
Entities: AI, Machine Learning
Category: tech
```

## Step 2: Conditional Routing

Route to different nodes based on state:

```php
final class ValidationState extends State
{
    public function __construct(
        public string $email = '',
        public bool $valid = false,
        public string $status = 'pending',
    ) {}

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'valid' => $this->valid,
            'status' => $this->status,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'] ?? '',
            valid: $data['valid'] ?? false,
            status: $data['status'] ?? 'pending',
        );
    }
}

class EmailValidator
{
    public static function run()
    {
        $graph = new StateGraph();

        // Validation node
        $graph->addNode('validate', fn(ValidationState $s) => {
            $isValid = filter_var($s->email, FILTER_VALIDATE_EMAIL) !== false;
            return $s->with(['valid' => $isValid]);
        });

        // Success node
        $graph->addNode('accept', fn(ValidationState $s) =>
            $s->with(['status' => 'accepted'])
        );

        // Rejection node
        $graph->addNode('reject', fn(ValidationState $s) =>
            $s->with(['status' => 'rejected'])
        );

        $graph->setEntryPoint('validate');

        // Conditional edge: route based on validation result
        $graph->addConditionalEdge('validate', fn(ValidationState $s) =>
            $s->valid ? 'accept' : 'reject'
        );

        $graph->addEdge('accept', StateGraph::END);
        $graph->addEdge('reject', StateGraph::END);

        // Test valid email
        $result1 = $graph->compile()->invoke(new ValidationState(
            email: 'alice@example.com'
        ));
        echo "Email: {$result1->email} -> Status: {$result1->status}\n";

        // Test invalid email
        $result2 = $graph->compile()->invoke(new ValidationState(
            email: 'invalid-email'
        ));
        echo "Email: {$result2->email} -> Status: {$result2->status}\n";
    }
}

EmailValidator::run();
// Output:
// Email: alice@example.com -> Status: accepted
// Email: invalid-email -> Status: rejected
```

## Step 3: Multi-Stage Workflow

```php
final class ProcessingState extends State
{
    public function __construct(
        public string $data = '',
        public int $score = 0,
        public string $result = '',
        public int $retries = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'score' => $this->score,
            'result' => $this->result,
            'retries' => $this->retries,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            data: $data['data'] ?? '',
            score: $data['score'] ?? 0,
            result: $data['result'] ?? '',
            retries: $data['retries'] ?? 0,
        );
    }
}

class QualityAssurance
{
    public static function run()
    {
        $graph = new StateGraph();

        // Step 1: Assess quality
        $graph->addNode('assess', fn(ProcessingState $s) => {
            $score = rand(1, 100);  // Simulated assessment
            return $s->with(['score' => $score]);
        });

        // Step 2: Approve (high quality)
        $graph->addNode('approve', fn(ProcessingState $s) =>
            $s->with(['result' => 'APPROVED'])
        );

        // Step 3: Reject (low quality)
        $graph->addNode('reject', fn(ProcessingState $s) =>
            $s->with(['result' => 'REJECTED'])
        );

        // Step 4: Review (medium quality)
        $graph->addNode('review', fn(ProcessingState $s) =>
            $s->with(['result' => 'NEEDS_REVIEW'])
        );

        $graph->setEntryPoint('assess');

        // Route based on score
        $graph->addConditionalEdge('assess', fn(ProcessingState $s) =>
            match(true) {
                $s->score >= 80 => 'approve',
                $s->score < 50 => 'reject',
                default => 'review',
            }
        );

        $graph->addEdge('approve', StateGraph::END);
        $graph->addEdge('reject', StateGraph::END);
        $graph->addEdge('review', StateGraph::END);

        $compiled = $graph->compile();
        $result = $compiled->invoke(new ProcessingState(data: 'sample data'));

        echo "Quality Score: {$result->score}\n";
        echo "Result: {$result->result}\n";
    }
}

QualityAssurance::run();
```

## Step 4: Looping (Retries)

Route back to an earlier node to retry:

```php
final class RetryState extends State
{
    public function __construct(
        public string $task = '',
        public ?string $result = null,
        public int $attempts = 0,
        public array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'task' => $this->task,
            'result' => $this->result,
            'attempts' => $this->attempts,
            'errors' => $this->errors,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            task: $data['task'] ?? '',
            result: $data['result'] ?? null,
            attempts: $data['attempts'] ?? 0,
            errors: $data['errors'] ?? [],
        );
    }
}

class ResilientTask
{
    public static function run()
    {
        $graph = new StateGraph();

        // Execute node
        $graph->addNode('execute', fn(RetryState $s) => {
            // Simulate random failures
            $success = rand(1, 100) > 40;  // 60% success rate
            
            if ($success) {
                return $s->with(['result' => 'SUCCESS']);
            } else {
                return $s->with([
                    'errors' => [...$s->errors, 'Execution failed'],
                    'attempts' => $s->attempts + 1,
                ]);
            }
        });

        // Wait node (before retrying)
        $graph->addNode('wait', fn(RetryState $s) => {
            sleep(1);  // Exponential backoff
            return $s;
        });

        // Final failure node
        $graph->addNode('fail', fn(RetryState $s) =>
            $s->with(['result' => 'FAILED_AFTER_RETRIES'])
        );

        $graph->setEntryPoint('execute');

        // Route: retry, succeed, or fail
        $graph->addConditionalEdge('execute', fn(RetryState $s) =>
            $s->result === 'SUCCESS' ? 'success' :
            ($s->attempts < 3 ? 'wait' : 'fail')
        );

        // Wait loops back to execute
        $graph->addEdge('wait', 'execute');

        $graph->addNode('success', fn(RetryState $s) => $s);
        $graph->addEdge('success', StateGraph::END);
        $graph->addEdge('fail', StateGraph::END);

        $compiled = $graph->compile();
        $result = $compiled->invoke(new RetryState(task: 'important_task'));

        echo "Result: {$result->result}\n";
        echo "Attempts: {$result->attempts}\n";
    }
}

ResilientTask::run();
```

## Step 5: Real-World: Approval Workflow

```php
final class ApprovalState extends State
{
    public function __construct(
        public string $request = '',
        public float $amount = 0.0,
        public int $approvalLevel = 0,
        public string $status = 'pending',
    ) {}

    public function toArray(): array
    {
        return [
            'request' => $this->request,
            'amount' => $this->amount,
            'approvalLevel' => $this->approvalLevel,
            'status' => $this->status,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            request: $data['request'] ?? '',
            amount: $data['amount'] ?? 0.0,
            approvalLevel: $data['approvalLevel'] ?? 0,
            status: $data['status'] ?? 'pending',
        );
    }
}

class ApprovalEngine
{
    public static function run()
    {
        $graph = new StateGraph();

        // Level 1: Manager approval
        $graph->addNode('manager_review', fn(ApprovalState $s) => {
            $approved = $s->amount < 1000;
            return $s->with([
                'approvalLevel' => 1,
                'status' => $approved ? 'level1_pass' : 'level1_fail',
            ]);
        });

        // Level 2: Director approval
        $graph->addNode('director_review', fn(ApprovalState $s) => {
            $approved = $s->amount < 10000;
            return $s->with([
                'approvalLevel' => 2,
                'status' => $approved ? 'level2_pass' : 'level2_fail',
            ]);
        });

        // Level 3: Executive approval
        $graph->addNode('exec_review', fn(ApprovalState $s) => {
            return $s->with([
                'approvalLevel' => 3,
                'status' => 'approved',
            ]);
        });

        // Rejection node
        $graph->addNode('reject', fn(ApprovalState $s) =>
            $s->with(['status' => 'rejected'])
        );

        $graph->setEntryPoint('manager_review');

        // Routing logic
        $graph->addConditionalEdge('manager_review', fn(ApprovalState $s) =>
            $s->status === 'level1_fail' ? 'reject' : 'director_review'
        );

        $graph->addConditionalEdge('director_review', fn(ApprovalState $s) =>
            match($s->status) {
                'level2_fail' => 'reject',
                'level2_pass' => $s->amount > 5000 ? 'exec_review' : 'finish',
                default => 'finish',
            }
        );

        $graph->addEdge('exec_review', 'finish');
        $graph->addNode('finish', fn(ApprovalState $s) => $s);
        $graph->addEdge('finish', StateGraph::END);
        $graph->addEdge('reject', StateGraph::END);

        // Test cases
        foreach ([[500, 'small'], [7500, 'medium'], [50000, 'large']] as [$amt, $label]) {
            $result = $graph->compile()->invoke(new ApprovalState(
                request: "$label request",
                amount: $amt
            ));
            echo "$label ($amt): {$result->status}\n";
        }
    }
}

ApprovalEngine::run();
// Output:
// small (500): approved
// medium (7500): approved
// large (50000): rejected
```

## Exercises

### Exercise 1: Content Moderation
Build a graph that:
1. Analyzes content safety
2. Routes to manual review if borderline
3. Approves or rejects

```php
// 👉 Your code here
```

### Exercise 2: Ticket Triage
Build a graph that:
1. Categorizes incoming tickets
2. Routes to appropriate team
3. Escalates if needed

```php
// 👉 Your code here
```

### Exercise 3: Multi-Step Validation
Build a graph that:
1. Validates input format
2. Checks against rules
3. Triggers retry or approval

```php
// 👉 Your code here
```

## Key Takeaways

✅ State graphs are immutable, deterministic workflows  
✅ Nodes are processing steps, edges are connections  
✅ Conditional edges enable branching logic  
✅ You can loop back to retry  
✅ StateGraph::END terminates execution  

## Next Steps

→ Ready to add intelligence? Go to [Tutorial 5: RAG](./05-advanced-rag.md)

Or explore [Tutorial 6: Production Patterns](./06-production-patterns.md) for deployment.

---

**Excellent work!** 🎉

