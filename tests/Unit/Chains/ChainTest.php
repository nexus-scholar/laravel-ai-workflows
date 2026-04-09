<?php

declare(strict_types=1);

use Laravel\Ai\Ai;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\StructuredAnonymousAgent;
use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Chains\Support\ProviderOptionsAgent;
use Nexus\Workflow\Chains\Support\StructuredProviderOptionsAgent;
use Nexus\Workflow\Contracts\Retriever;
use Nexus\Workflow\Memory\InMemoryConversation;
use Nexus\Workflow\Prompts\PromptTemplate;

use function Laravel\Ai\agent;

it('runs a chain using laravel ai fake agent', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Done']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Question: {input}')
    );

    $result = $chain->run(['input' => 'What is Laravel AI?']);

    expect($result)->toBe('Done');

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) => str_contains($prompt->prompt, 'Question: What is Laravel AI?')
    );
});

it('returns structured data when laravel ai returns structured response', function () {
    Ai::fakeAgent(StructuredAnonymousAgent::class, [[
        'summary' => 'Structured',
        'score' => 0.98,
    ]]);

    $chain = Chain::make(
        agent(schema: fn ($schema) => [
            'summary' => $schema->string()->required(),
            'score' => $schema->number()->required(),
        ]),
        PromptTemplate::from('Summarize: {input}')
    );

    $result = $chain->run(['input' => 'Paper content']);

    expect($result)
        ->toBeArray()
        ->toHaveKeys(['summary', 'score']);
});

it('streams text deltas from laravel ai stream events', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['hello streaming world']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Stream: {input}')
    );

    $chunks = iterator_to_array($chain->stream(['input' => 'go']), false);
    $text = implode('', $chunks);

    expect($text)
        ->toContain('hello')
        ->toContain('streaming')
        ->toContain('world');
});

it('streams native laravel ai events via streamEvents', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['hello streaming world']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Stream events: {input}')
    );

    $events = iterator_to_array($chain->streamEvents(['input' => 'go']), false);

    expect($events)->not->toBeEmpty()
        ->and($events[0])->toBeInstanceOf(\Laravel\Ai\Streaming\Events\StreamStart::class)
        ->and(array_filter($events, fn ($event) => $event instanceof \Laravel\Ai\Streaming\Events\TextDelta))->not->toBeEmpty();
});

it('stores prompt and response in memory', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Saved']);

    $memory = new InMemoryConversation;

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Echo: {input}')
    )->withMemory($memory);

    $chain->run(['input' => 'persist this']);

    expect($memory->messages())
        ->toHaveCount(2)
        ->and($memory->messages()[0]['role'])->toBe('human')
        ->and($memory->messages()[0]['content'])->toBe('persist this')
        ->and($memory->messages()[1]['role'])->toBe('ai')
        ->and($memory->messages()[1]['content'])->toBe('Saved');
});

it('passes custom model override to laravel ai', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Model OK']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Model check: {input}')
    )->withModel('gpt-4o-mini');

    $chain->run(['input' => 'test']);

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) => $prompt->model === 'gpt-4o-mini'
    );
});

it('passes timeout override to laravel ai', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Timeout OK']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Timeout check: {input}')
    )->withTimeout(120);

    $chain->run(['input' => 'ping']);

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) => $prompt->timeout === 120);
});

it('passes attachments to laravel ai', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Attachment OK']);

    $attachment = (object) ['name' => 'demo.txt'];

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Attachment check: {input}')
    )->withAttachments([$attachment]);

    $chain->run(['input' => 'ping']);

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) => $prompt->attachments->count() === 1);
});

it('passes failover provider arrays to laravel ai', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Failover OK']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Failover check: {input}')
    )->withProvider(['openai', 'anthropic']);

    $chain->run(['input' => 'ping']);

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) => in_array($prompt->provider()->name(), ['openai', 'anthropic'], true));
});

it('runs chain with provider options array using provider-options wrapper', function () {
    Ai::fakeAgent(ProviderOptionsAgent::class, ['Provider options OK']);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Options check: {input}')
    )->withProviderOptions([
        'openai' => ['temperature' => 0.2],
    ])->withProvider('openai');

    $result = $chain->run(['input' => 'ping']);

    expect($result)->toBe('Provider options OK');
    Ai::assertAgentWasPrompted(ProviderOptionsAgent::class, fn ($prompt) => str_contains($prompt->prompt, 'Options check: ping'));
});

it('runs structured chain with provider options resolver using structured wrapper', function () {
    Ai::fakeAgent(StructuredProviderOptionsAgent::class, [[
        'summary' => 'ok',
    ]]);

    $chain = Chain::make(
        agent(schema: fn ($schema) => ['summary' => $schema->string()->required()]),
        PromptTemplate::from('Structured options check: {input}')
    )->withProviderOptionsResolver(fn () => ['reasoning' => ['effort' => 'low']]);

    $result = $chain->run(['input' => 'ping']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('summary');
    Ai::assertAgentWasPrompted(StructuredProviderOptionsAgent::class, fn ($prompt) => str_contains($prompt->prompt, 'Structured options check: ping'));
});

it('throws when retriever is configured without an explicit input key', function () {
    $retriever = Mockery::mock(Retriever::class);
    $retriever->shouldNotReceive('retrieve');

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Context: {context}')
    )->withRetriever($retriever);

    expect(fn () => $chain->run(['topic' => 'graph orchestration']))
        ->toThrow(InvalidArgumentException::class, "Retriever-enabled chains require an 'input' key.");
});

it('throws when input key contains a non scalar value', function () {
    $retriever = Mockery::mock(Retriever::class);
    $retriever->shouldNotReceive('retrieve');

    $chain = Chain::make(
        agent(),
        PromptTemplate::from('Context: {context}')
    )->withRetriever($retriever);

    expect(fn () => $chain->run(['input' => ['nested']]))
        ->toThrow(InvalidArgumentException::class, "Input value for key 'input' must be scalar or Stringable.");
});
