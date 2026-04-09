<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Graph;

use Illuminate\Support\Facades\Bus;
use Nexus\Workflow\Graph\Runners\QueueRunner;
use Nexus\Workflow\Graph\State;
use Nexus\Workflow\Graph\StateGraph;
use Nexus\Workflow\Jobs\RunGraphNode;
use Nexus\Workflow\Tests\TestCase;
use RuntimeException;

if (! class_exists(CounterState::class)) {
    class CounterState extends State
    {
        public function __construct(public int $count = 0) {}

        public function toArray(): array
        {
            return ['count' => $this->count];
        }

        public static function fromArray(array $data): static
        {
            return new self($data['count']);
        }
    }
}

class QueueRunnerTest extends TestCase
{
    public function test_it_dispatches_the_first_node_job_with_graph_resolver()
    {
        Bus::fake();

        $graph = new StateGraph;
        $graph->addNode('start', fn ($s) => $s);
        $graph->setEntryPoint('start');
        $graph->addEdge('start', StateGraph::END);

        $compiled = $graph->compile();

        $this->app->bind('test.queue.graph', fn () => $compiled);

        $runner = new QueueRunner($compiled, 'test.queue.graph');

        $runId = $runner->dispatch(new CounterState(0));

        $this->assertIsString($runId);

        Bus::assertDispatched(RunGraphNode::class);
    }

    public function test_it_fails_when_graph_is_not_queue_safe_and_no_resolver_is_provided()
    {
        $graph = new StateGraph;
        $graph->addNode('start', fn ($s) => $s);
        $graph->setEntryPoint('start');
        $graph->addEdge('start', StateGraph::END);

        $compiled = $graph->compile();
        $runner = new QueueRunner($compiled);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Graph is not queue-safe for serialization.');

        $runner->dispatch(new CounterState(0));
    }
}
