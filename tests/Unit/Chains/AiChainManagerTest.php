<?php

declare(strict_types=1);

use Nexus\Workflow\AiChainManager;
use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Chains\ChainFactory;
use Nexus\Workflow\Contracts\Chain as ChainContract;
use Nexus\Workflow\Prompts\PromptTemplate;

use function Laravel\Ai\agent;

it('creates a fluent chain factory from agent and prompt', function () {
    $manager = new AiChainManager;

    $factory = $manager->chain(
        agent(),
        PromptTemplate::from('Q: {input}'),
        'draft'
    );

    expect($factory)->toBeInstanceOf(ChainFactory::class);
    expect($factory->build())->toBeInstanceOf(Chain::class);
});

it('creates a fluent factory from an existing chain', function () {
    $manager = new AiChainManager;
    $chain = Mockery::mock(ChainContract::class);

    $factory = $manager->from($chain);

    expect($factory)->toBeInstanceOf(ChainFactory::class);
});

