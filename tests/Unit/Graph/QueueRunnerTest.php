<?php

declare(strict_types=1);

namespace NexusScholar\AiChain\Tests\Unit\Graph;

use Illuminate\Support\Facades\Bus;
use NexusScholar\AiChain\Graph\StateGraph;
use NexusScholar\AiChain\Graph\Runners\QueueRunner;
use NexusScholar\AiChain\Jobs\RunGraphNode;
use NexusScholar\AiChain\Tests\TestCase;

if (!class_exists(CounterState::class)) {
    class CounterState extends State
    {
        public function __construct(public int $count = 0) {}
        public function toArray(): array { return ['count' => $this->count]; }
        public static function fromArray(array $data): static { return new self($data['count']); }
    }
}

class QueueRunnerTest extends TestCase
{
    public function test_it_dispatches_the_first_node_job()
    {
        Bus::fake();

        $graph = new StateGraph();
        $graph->addNode('start', fn($s) => $s);
        $graph->setEntryPoint('start');
        $graph->addEdge('start', StateGraph::END);
        
        $compiled = $graph->compile();
        $runner = new QueueRunner($compiled);
        
        $runId = $runner->dispatch(new CounterState(0));

        $this->assertIsString($runId);
        
        Bus::assertDispatched(RunGraphNode::class);
    }
}
