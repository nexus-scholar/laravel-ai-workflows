<?php

declare(strict_types=1);

use Nexus\AiChain\AiChainServiceProvider;
use Nexus\AiChain\AiChainManager;
use Nexus\AiChain\Contracts\Checkpointable;
use Nexus\AiChain\Graph\Checkpoint\CacheCheckpoint;

it('merges default package config', function () {
    expect(config('ai-chain.graph.max_iterations'))->toBe(50)
        ->and(config('ai-chain.graph.checkpoint.store'))->toBe('file')
        ->and(config('ai-chain.memory.max_messages'))->toBe(20)
        ->and(config('ai-chain.retrieval.hybrid_rrf_k'))->toBe(60);
});

it('binds checkpoint contract to cache checkpoint', function () {
    $checkpoint = app()->make(Checkpointable::class);

    expect($checkpoint)->toBeInstanceOf(CacheCheckpoint::class);
});

it('binds ai chain manager as singleton', function () {
    $first = app()->make(AiChainManager::class);
    $second = app()->make(AiChainManager::class);

    expect($first)->toBeInstanceOf(AiChainManager::class)
        ->and($first)->toBe($second);
});

it('registers config publish path', function () {
    $publishPaths = AiChainServiceProvider::pathsToPublish(
        AiChainServiceProvider::class,
        'ai-chain-config'
    );

    expect($publishPaths)->not->toBeEmpty();
});

