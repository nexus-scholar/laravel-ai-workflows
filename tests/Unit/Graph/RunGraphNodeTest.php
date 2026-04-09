<?php

declare(strict_types=1);

namespace Nexus\AiChain\Tests\Unit\Graph;

use Mockery;
use Nexus\AiChain\Contracts\Checkpointable;
use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Graph\StateGraph;
use Nexus\AiChain\Jobs\RunGraphNode;
use Nexus\AiChain\Tests\TestCase;

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

class RunGraphNodeTest extends TestCase
{
    public function test_it_persists_checkpoint_after_node_execution(): void
    {
        $graph = new StateGraph;
        $graph->addNode('start', fn (CounterState $state) => $state->with(['count' => $state->count + 1]));
        $graph->setEntryPoint('start');
        $graph->addEdge('start', StateGraph::END);

        $checkpoint = Mockery::mock(Checkpointable::class);
        $checkpoint->shouldReceive('save')->once()->with(
            'run_1',
            'start',
            Mockery::on(fn ($state) => $state instanceof CounterState && $state->count === 1)
        );

        $compiled = $graph->compile()->withCheckpoint($checkpoint);

        $job = new RunGraphNode(
            $compiled,
            new CounterState(0),
            'start',
            'run_1'
        );

        $job->handle();
    }
}

