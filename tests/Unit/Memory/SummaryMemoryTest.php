<?php

declare(strict_types=1);

use Nexus\AiChain\Memory\SummaryMemory;

it('calls summarizer when threshold is reached', function () {
    $summarizerCalled = false;
    $summarizer = function ($history, $prev) use (&$summarizerCalled) {
        $summarizerCalled = true;

        return 'Summary: '.strlen($history);
    };

    $memory = new SummaryMemory($summarizer, summarizeAfter: 2);

    $memory->add('human', 'Hi');
    expect($summarizerCalled)->toBeFalse();

    $memory->add('ai', 'Hello');
    expect($summarizerCalled)->toBeTrue();
    expect($memory->asString())->toContain('Summary: ');
});

it('preserves recent messages after summarization', function () {
    $summarizer = fn ($h, $p) => 'Short summary';
    $memory = new SummaryMemory($summarizer, summarizeAfter: 4);

    $memory->add('h', '1');
    $memory->add('a', '2');
    $memory->add('h', '3');
    $memory->add('a', '4');

    // After adding 4th message, it summarizes the first 2 (half of 4)
    // Messages remaining: '3', '4'
    expect($memory->messages())->toHaveCount(2);
    expect($memory->messages()[0]['content'])->toBe('3');
    expect($memory->asString())->toContain('SUMMARY OF EARLIER CONVERSATION:');
    expect($memory->asString())->toContain('H: 3');
});

it('does not drop messages when summarizer throws', function () {
    $summarizer = function () {
        throw new RuntimeException('summary failed');
    };

    $memory = new SummaryMemory($summarizer, summarizeAfter: 2);
    $memory->add('h', '1');
    $memory->add('a', '2');

    expect($memory->messages())->toHaveCount(2);
    expect($memory->asString())->toContain('H: 1');
});

it('does not drop messages when summarizer returns empty text', function () {
    $memory = new SummaryMemory(fn () => '   ', summarizeAfter: 2);

    $memory->add('h', '1');
    $memory->add('a', '2');

    expect($memory->messages())->toHaveCount(2);
    expect($memory->asString())->not->toContain('SUMMARY OF EARLIER CONVERSATION:');
});

it('validates constructor arguments', function () {
    expect(fn () => new SummaryMemory('not_callable', summarizeAfter: 2))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new SummaryMemory(fn () => 'ok', summarizeAfter: 1))
        ->toThrow(InvalidArgumentException::class);
});

