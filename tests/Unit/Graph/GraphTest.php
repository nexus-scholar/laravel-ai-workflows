<?php

declare(strict_types=1);

namespace NexusScholar\AiChain\Tests\Unit\Graph;

use NexusScholar\AiChain\Graph\State;
use NexusScholar\AiChain\Graph\StateGraph;
use NexusScholar\AiChain\Tests\TestCase;

class CounterState extends State
{
    public function __construct(public int $count = 0) {}

    public function toArray(): array { return ['count' => $this->count]; }
    public static function fromArray(array $data): static { return new self($data['count']); }
}

it('executes a linear graph', function () {
    $graph = new StateGraph();
    
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
    $graph = new StateGraph();

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
    $graph = new StateGraph();
    
    $graph->addNode('step1', fn(CounterState $s) => $s->with(['count' => 1]));
    $graph->addNode('step2', fn(CounterState $s) => $s->with(['count' => 2]));

    $graph->setEntryPoint('step1');
    $graph->addEdge('step1', 'step2');
    $graph->addEdge('step2', StateGraph::END);

    $compiled = $graph->compile();
    $steps = iterator_to_array($compiled->stream(new CounterState(0)));

    expect($steps)->toHaveCount(2);
    expect($steps['step1']->count)->toBe(1);
    expect($steps['step2']->count)->toBe(2);
});
