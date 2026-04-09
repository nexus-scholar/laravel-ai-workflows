<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Graph;

use Mockery;
use Nexus\Workflow\Contracts\Checkpointable;
use Nexus\Workflow\Graph\StateGraph;
use Nexus\Workflow\Jobs\RunGraphNode;
use Nexus\Workflow\Tests\TestCase;


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

