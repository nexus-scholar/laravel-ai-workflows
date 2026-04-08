<?php

namespace NexusScholar\AiChain\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use NexusScholar\AiChain\Contracts\Chain;
use Stringable;

final readonly class ChainTool implements Tool
{
    public function __construct(
        private Chain  $chain,
        private string $name,
        private string $description,
    ) {}

    public function description(): Stringable|string
    {
        return $this->description;
    }

    public function handle(Request $request): Stringable|string
    {
        $result = $this->chain->run($request->all());

        return is_array($result) ? json_encode($result) : (string) $result;
    }

    public function schema(JsonSchema $schema): array
    {
        $definition = [];
        
        foreach ($this->chain->inputKeys() as $key) {
            // We assume string inputs for the chain tool schema by default.
            // Complex types would need more advanced mapping.
            $definition[$key] = $schema->string()->required();
        }

        return $definition;
    }

    public function name(): string
    {
        return $this->name;
    }
}
