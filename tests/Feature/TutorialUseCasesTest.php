<?php

declare(strict_types=1);

use Laravel\Ai\Ai;
use Laravel\Ai\AnonymousAgent;
use Nexus\Workflow\AiChainManager;
use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Graph\StateGraph;
use Nexus\Workflow\Memory\InMemoryConversation;
use Nexus\Workflow\Prompts\PromptTemplate;
use Nexus\Workflow\Retrieval\VectorStoreRetriever;
use Nexus\Workflow\Tests\Feature\UseCaseState;

use function Laravel\Ai\agent;


it('supports tutorial use case: single chain', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['Concise answer']);

    $chain = Chain::make(agent(), PromptTemplate::from('Q: {input}'));

    expect($chain->run(['input' => 'What is retrieval?']))->toBe('Concise answer');
});

it('supports tutorial use case: chain with memory', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['first answer', 'second answer']);

    $memory = new InMemoryConversation;

    $chain = Chain::make(
        agent(),
        PromptTemplate::from("History:\n{history}\n\nQ: {input}")
    )->withMemory($memory);

    $chain->run(['input' => 'Explain chunking']);
    $chain->run(['input' => 'Now summarize in one line']);

    expect($memory->messages())->toHaveCount(4);

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) =>
        str_contains($prompt->prompt, 'History:')
        && str_contains($prompt->prompt, 'first answer')
    );
});

it('supports tutorial use case: chain with retrieval context', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['grounded response']);

    $retriever = new VectorStoreRetriever(fn () => [
        ['content' => 'Document A'],
        ['content' => 'Document B'],
    ]);

    $chain = Chain::make(
        agent(),
        PromptTemplate::from("Context:\n{context}\n\nQ: {input}")
    )->withRetriever($retriever, 2);

    $chain->run(['input' => 'How does RAG reduce hallucinations?']);

    Ai::assertAgentWasPrompted(AnonymousAgent::class, fn ($prompt) =>
        str_contains($prompt->prompt, 'Document A')
        && str_contains($prompt->prompt, 'Document B')
    );
});

it('supports tutorial use case: state graph workflow', function () {
    $graph = new StateGraph;

    $graph->addNode('collect', fn (UseCaseState $s) => $s->with([
        'count' => $s->count + 1,
        'events' => array_merge($s->events, ['collect']),
    ]));

    $graph->addNode('draft', fn (UseCaseState $s) => $s->with([
        'count' => $s->count + 1,
        'events' => array_merge($s->events, ['draft']),
    ]));

    $graph->setEntryPoint('collect');
    $graph->addEdge('collect', 'draft');
    $graph->addEdge('draft', StateGraph::END);

    $result = $graph->compile()->invoke(new UseCaseState);

    expect($result->count)->toBe(2)
        ->and($result->events)->toBe(['collect', 'draft']);
});

it('supports tutorial use case: manager and factory composition', function () {
    Ai::fakeAgent(AnonymousAgent::class, ['stage 1 output', 'stage 2 output']);

    $manager = new AiChainManager;

    $pipeline = $manager
        ->chain(agent(), PromptTemplate::from('Q: {input}'), 'draft')
        ->thenPrompt(agent(), PromptTemplate::from('Draft: {draft}'), 'final')
        ->build();

    expect($pipeline->run(['input' => 'Explain embeddings in 2 stages']))
        ->toBe('stage 2 output');
});

