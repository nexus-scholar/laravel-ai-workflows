<?php

declare(strict_types=1);

use Nexus\AiChain\Prompts\ChatPromptTemplate;

it('formats multiple messages correctly', function () {
    $template = ChatPromptTemplate::fromMessages([
        ['role' => 'system', 'template' => 'You are a {profession}.'],
        ['role' => 'human', 'template' => 'Tell me about {topic}.'],
    ]);

    $result = $template->format(['profession' => 'bot', 'topic' => 'space']);

    expect($result)->toBe([
        ['role' => 'system', 'content' => 'You are a bot.'],
        ['role' => 'human', 'content' => 'Tell me about space.'],
    ]);
});

it('throws exception if a message template is missing variables', function () {
    $template = ChatPromptTemplate::fromMessages([
        ['role' => 'system', 'template' => 'You are a {profession}.'],
        ['role' => 'human', 'template' => 'Tell me about {topic}.'],
    ]);

    expect(fn () => $template->format(['topic' => 'space']))
        ->toThrow(InvalidArgumentException::class, 'Missing prompt variables: profession');
});
