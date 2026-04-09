<?php

declare(strict_types=1);

use Nexus\Workflow\Memory\InMemoryConversation;

it('adds and retrieves messages', function () {
    $memory = new InMemoryConversation;
    $memory->add('human', 'Hi');
    $memory->add('ai', 'Hello');

    expect($memory->messages())->toBe([
        ['role' => 'human', 'content' => 'Hi'],
        ['role' => 'ai', 'content' => 'Hello'],
    ]);
});

it('formats messages as string', function () {
    $memory = new InMemoryConversation;
    $memory->add('human', 'Hi');
    $memory->add('ai', 'Hello');

    expect($memory->asString())->toBe("HUMAN: Hi\nAI: Hello");
});

it('clears messages', function () {
    $memory = new InMemoryConversation;
    $memory->add('human', 'Hi');
    $memory->clear();

    expect($memory->messages())->toBe([]);
});
