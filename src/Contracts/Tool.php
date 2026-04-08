<?php

namespace NexusScholar\AiChain\Contracts;

interface Tool
{
    public function name(): string;
    public function description(): string;
    public function inputSchema(): array; // JSON Schema
    public function handle(array $args): string;
}
