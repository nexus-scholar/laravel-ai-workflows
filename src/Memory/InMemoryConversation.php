<?php

namespace Nexus\AiChain\Memory;

use Nexus\AiChain\Contracts\Memory;

final class InMemoryConversation implements Memory
{
    private array $messages = [];

    public function add(string $role, string $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
    }

    public function messages(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
    }

    public function asString(): string
    {
        return implode("\n", array_map(
            fn ($m) => strtoupper($m['role']).': '.$m['content'],
            $this->messages
        ));
    }
}
