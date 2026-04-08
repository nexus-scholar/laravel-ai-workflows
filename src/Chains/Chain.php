<?php

namespace Nexus\AiChain\Chains;

use Nexus\AiChain\Contracts\Chain as ChainContract;
use Nexus\AiChain\Contracts\Memory;
use Nexus\AiChain\Contracts\Retriever;
use Nexus\AiChain\Prompts\PromptTemplate;

final class Chain implements ChainContract
{
    private ?Memory $memory = null;

    private ?Retriever $retriever = null;

    private int $topK = 5;

    private function __construct(
        private readonly object $agent,
        private readonly PromptTemplate $promptTemplate,
        private readonly string $outputKey = 'output',
    ) {}

    public static function make(
        object $agent,
        PromptTemplate $promptTemplate,
        string $outputKey = 'output',
    ): self {
        return new self($agent, $promptTemplate, $outputKey);
    }

    public function withMemory(Memory $memory): self
    {
        $clone = clone $this;
        $clone->memory = $memory;

        return $clone;
    }

    public function withRetriever(Retriever $retriever, int $topK = 5): self
    {
        $clone = clone $this;
        $clone->retriever = $retriever;
        $clone->topK = $topK;

        return $clone;
    }

    public function run(array $inputs): mixed
    {
        $augmented = $this->augmentInputs($inputs);
        $prompt = $this->promptTemplate->format($augmented);

        $response = $this->agent->prompt($prompt);
        $raw = $response->text();

        $this->memory?->add('human', (string) ($inputs['input'] ?? array_values($inputs)[0]));
        $this->memory?->add('ai', $raw);

        // Native structured output via laravel/ai
        if (isset($response->structured)) {
            return $response->structured;
        }

        return $raw;
    }

    public function stream(array $inputs): \Generator
    {
        $augmented = $this->augmentInputs($inputs);
        $prompt = $this->promptTemplate->format($augmented);

        foreach ($this->agent->stream($prompt) as $chunk) {
            yield $chunk->text();
        }
    }

    public function inputKeys(): array
    {
        $keys = $this->promptTemplate->inputVariables();
        if ($this->retriever) {
            $keys[] = 'context';
        }
        if ($this->memory) {
            $keys[] = 'history';
        }

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
            $docs = $this->retriever->retrieve((string) $query, $this->topK);
            $inputs['context'] = implode("\n\n", array_map(fn ($d) => $d->content, $docs));
        }

        if ($this->memory !== null) {
            $inputs['history'] = $this->memory->asString();
        }

        return $inputs;
    }
}
