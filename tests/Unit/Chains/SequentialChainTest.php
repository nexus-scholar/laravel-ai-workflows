<?php

declare(strict_types=1);

use Nexus\Workflow\Chains\SequentialChain;
use Nexus\Workflow\Contracts\Chain as ChainContract;

it('runs chains sequentially and returns last output', function () {
    $first = Mockery::mock(ChainContract::class);
    $second = Mockery::mock(ChainContract::class);

    $first->shouldReceive('run')->once()->with(['input' => 'q'])->andReturn('first_result');
    $first->shouldReceive('outputKey')->andReturn('first');

    $second->shouldReceive('run')->once()->with(['input' => 'q', 'first' => 'first_result'])->andReturn('second_result');
    $second->shouldReceive('outputKey')->andReturn('second');

    $chain = new SequentialChain([$first, $second]);

    expect($chain->run(['input' => 'q']))->toBe('second_result');
});

it('streams only the final chain after preparing previous outputs', function () {
    $first = Mockery::mock(ChainContract::class);
    $second = Mockery::mock(ChainContract::class);

    $first->shouldReceive('run')->once()->with(['input' => 'q'])->andReturn('first_result');
    $first->shouldReceive('outputKey')->andReturn('first');

    $second->shouldReceive('stream')->once()->with(['input' => 'q', 'first' => 'first_result'])
        ->andReturn((function () {
            yield 'a';
            yield 'b';
        })());

    $chain = new SequentialChain([$first, $second]);

    expect(iterator_to_array($chain->stream(['input' => 'q']), false))->toBe(['a', 'b']);
});

it('fails fast on empty chain list', function () {
    expect(fn () => new SequentialChain([]))
        ->toThrow(InvalidArgumentException::class, 'SequentialChain requires at least one chain.');
});

