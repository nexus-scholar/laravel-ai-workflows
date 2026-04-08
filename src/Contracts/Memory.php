<?php

namespace Nexus\AiChain\Contracts;

interface Memory
{
    public function add(string $role, string $content): void;

    /** @return array<array{role: string, content: string}> */
    public function messages(): array;

    public function clear(): void;

    /** Serialize to string for injection into prompts */
    public function asString(): string;
}
