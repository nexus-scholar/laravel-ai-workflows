<?php

namespace NexusScholar\AiChain\Contracts;

interface ToolRegistry
{
    public function register(Tool $tool): void;
    public function get(string $name): ?Tool;
    /** @return Tool[] */
    public function all(): array;
}
