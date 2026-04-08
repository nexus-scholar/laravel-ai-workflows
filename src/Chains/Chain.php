<?php

namespace NexusScholar\AiChain\Chains;

use NexusScholar\AiChain\Contracts\Chain as ChainContract;
use NexusScholar\AiChain\Contracts\OutputParser;
use NexusScholar\AiChain\Prompts\PromptTemplate;

final class Chain implements ChainContract
{
    private ?\NexusScholar\AiChain\Contracts\Memory    $memory    = null;
    private ?\NexusScholar\AiChain\Contracts\Retriever $retriever = null;
    private int        $topK      = 5;

    private function __construct(
        private readonly object       $agent,
        private readonly PromptTemplate $promptTemplate,
        private readonly OutputParser   $parser,
        private readonly string         $outputKey = 'output',
    ) {}

    public static function make(
        object $agent,
        PromptTemplate $promptTemplate,
        OutputParser $parser,
        string $outputKey = 'output',
    ): self {
        return new self($agent, $promptTemplate, $parser, $outputKey);
    }

    public function withMemory(\NexusScholar\AiChain\Contracts\Memory $memory): self
    {
        $clone = clone $this;
        $clone->memory = $memory;
        return $clone;
    }

    public function withRetriever(\NexusScholar\AiChain\Contracts\Retriever $retriever, int $topK = 5): self
    {
        $clone = clone $this;
        $clone->retriever  = $retriever;
        $clone->topK       = $topK;
        return $clone;
    }

    public function run(array $inputs): mixed
    {
        $augmented = $this->augmentInputs($inputs);
        $prompt    = $this->promptTemplate->format($augmented);
        
        $instructions = $this->parser->formatInstructions();
        if ($instructions !== '') {
            $prompt .= "\n\n" . $instructions;
        }

        $response = $this->agent->prompt($prompt);
        $raw = $response->text();

        $this->memory?->add('human', (string) ($inputs['input'] ?? array_values($inputs)[0]));
        $this->memory?->add('ai', $raw);

        return $this->parser->parse($raw);
    }

    public function stream(array $inputs): \Generator
    {
        $augmented = $this->augmentInputs($inputs);
        $prompt    = $this->promptTemplate->format($augmented);
        
        $instructions = $this->parser->formatInstructions();
        if ($instructions !== '') {
            $prompt .= "\n\n" . $instructions;
        }

        foreach ($this->agent->stream($prompt) as $chunk) {
            yield $chunk->text();
        }
    }

    public function inputKeys(): array
    {
        $keys = $this->promptTemplate->inputVariables();
        if ($this->retriever) $keys[] = 'context';
        if ($this->memory) $keys[] = 'history';
        return array_unique($keys);
    }

    public function outputKey(): string
    {
        return $this->outputKey;
    }

    private function augmentInputs(array $inputs): array
    {
        if ($this->retriever !== null) {
            $query = $inputs['input'] ?? array_values($inputs)[0];
            $docs  = $this->retriever->retrieve((string) $query, $this->topK);
            $inputs['context'] = implode("\n\n", array_map(fn ($d) => $d->content, $docs));
        }

        if ($this->memory !== null) {
            $inputs['history'] = $this->memory->asString();
        }

        return $inputs;
    }
}
