<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Graph;

use Nexus\Workflow\Graph\Exceptions\GraphValidationException;
use Nexus\Workflow\Graph\State;
use Nexus\Workflow\Graph\StateGraph;
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

it('executes a linear graph', function () {
    $graph = new StateGraph;

    $graph->addNode('increment', function (CounterState $state) {
        return $state->with(['count' => $state->count + 1]);
    });

    $graph->setEntryPoint('increment');
    $graph->addEdge('increment', StateGraph::END);

    $compiled = $graph->compile();
    $finalState = $compiled->invoke(new CounterState(0));

    expect($finalState->count)->toBe(1);
});

it('executes a conditional loop', function () {
    $graph = new StateGraph;

    $graph->addNode('add', function (CounterState $state) {
        return $state->with(['count' => $state->count + 1]);
    });

    $graph->setEntryPoint('add');

    $graph->addConditionalEdge('add', function (CounterState $state) {
        return $state->count < 3 ? 'add' : StateGraph::END;
    });

    $compiled = $graph->compile();
    $finalState = $compiled->invoke(new CounterState(0));

    // 0 -> add (1) -> check (<3) -> add (2) -> check (<3) -> add (3) -> check (not <3) -> END
    expect($finalState->count)->toBe(3);
});

it('streams graph execution', function () {
    $graph = new StateGraph;

    $graph->addNode('step1', fn (CounterState $s) => $s->with(['count' => 1]));
    $graph->addNode('step2', fn (CounterState $s) => $s->with(['count' => 2]));

    $graph->setEntryPoint('step1');
    $graph->addEdge('step1', 'step2');
    $graph->addEdge('step2', StateGraph::END);

    $compiled = $graph->compile();
    $steps = iterator_to_array($compiled->stream(new CounterState(0)));

    expect($steps)->toHaveCount(2)
        ->and($steps['step1']->count)->toBe(1)
        ->and($steps['step2']->count)->toBe(2);
});

it('fails when compiling with an unknown edge destination', function () {
    $graph = new StateGraph;
    $graph->addNode('start', fn (CounterState $s) => $s);
    $graph->setEntryPoint('start');
    $graph->addEdge('start', 'missing_node');

    expect(fn () => $graph->compile())
        ->toThrow(GraphValidationException::class, "Edge points to non-existent node 'missing_node'.");
});

it('fails when entry point is empty', function () {
    $graph = new StateGraph;

    expect(fn () => $graph->setEntryPoint(''))
        ->toThrow(GraphValidationException::class, 'Entry point cannot be empty.');
});

it('fails when a node returns a non state value', function () {
    $graph = new StateGraph;
    $graph->addNode('bad', fn (CounterState $s) => 'invalid');
    $graph->setEntryPoint('bad');
    $graph->addEdge('bad', StateGraph::END);

    $compiled = $graph->compile();

    expect(fn () => $compiled->invoke(new CounterState(0)))
        ->toThrow(RuntimeException::class, "Node 'bad' must return an instance of Nexus\\Workflow\\Graph\\State.");
});

it('fails when a conditional edge resolves to an unknown node', function () {
    $graph = new StateGraph;
    $graph->addNode('start', fn (CounterState $s) => $s);
    $graph->setEntryPoint('start');
    $graph->addConditionalEdge('start', fn (CounterState $s) => 'ghost');

    $compiled = $graph->compile();

    expect(fn () => $compiled->invoke(new CounterState(0)))
        ->toThrow(RuntimeException::class, "Conditional edge from 'start' routed to unknown node 'ghost'.");
});

