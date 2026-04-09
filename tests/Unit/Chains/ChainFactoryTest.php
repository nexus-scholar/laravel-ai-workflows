<?php

declare(strict_types=1);

use Laravel\Ai\AnonymousAgent;
use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Chains\ChainFactory;
use Nexus\Workflow\Chains\SequentialChain;
use Nexus\Workflow\Contracts\Chain as ChainContract;
use Nexus\Workflow\Prompts\PromptTemplate;

use function Laravel\Ai\agent;

it('build returns a single chain when only one step is added', function () {
    $chain = ChainFactory::chain(
        agent(),
        PromptTemplate::from('First {input}'),
        'first'
    )->build();

    expect($chain)->toBeInstanceOf(Chain::class);
});

it('build returns sequential chain when multiple steps are added', function () {
    $first = Mockery::mock(ChainContract::class);
    $second = Mockery::mock(ChainContract::class);

    $factory = ChainFactory::from($first)->then($second);
    $built = $factory->build();

    expect($built)->toBeInstanceOf(SequentialChain::class);
});

it('supports fluent compose from Chain static helper', function () {
    $first = Chain::make(agent(), PromptTemplate::from('First {input}'), 'first');
    $second = Chain::make(agent(), PromptTemplate::from('Second {first}'), 'second');

    $composed = Chain::compose(agent(), PromptTemplate::from('Root {input}'), 'root')
        ->then($first)
        ->then($second)
        ->buildSequential();

    expect($composed)->toBeInstanceOf(SequentialChain::class);
});

it('throws when building factory with no chains', function () {
    $reflection = new ReflectionClass(ChainFactory::class);
    $factory = $reflection->newInstanceWithoutConstructor();

    expect(fn () => $factory->build())
        ->toThrow(InvalidArgumentException::class);
});

