<?php

namespace NexusScholar\AiChain\Tools;

use NexusScholar\AiChain\Contracts\Tool;
use NexusScholar\AiChain\Contracts\ToolRegistry;

final class InMemoryToolRegistry implements ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    public function register(Tool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    public function all(): array
    {
        return array_values($this->tools);
    }
}
