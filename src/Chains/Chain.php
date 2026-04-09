<?php

namespace Nexus\Workflow\Chains;

use InvalidArgumentException;
use Laravel\Ai\Contracts\Agent;
use Nexus\Workflow\Contracts\Chain as ChainContract;
use Nexus\Workflow\Contracts\Memory;
use Nexus\Workflow\Contracts\Retriever;
use Nexus\Workflow\Prompts\PromptTemplate;
use Stringable;

final class Chain implements ChainContract
{
    private ?Memory $memory = null;

    private ?Retriever $retriever = null;

    private int $topK = 5;

    private string|array|null $provider = null;

    private ?string $model = null;

    private function __construct(
        private readonly Agent $agent,
        private readonly PromptTemplate $promptTemplate,
        private readonly string $outputKey = 'output',
    ) {}

    public static function make(
        Agent $agent,
        PromptTemplate $promptTemplate,
        string $outputKey = 'output',
    ): self {
        return new self($agent, $promptTemplate, $outputKey);
    }

    public static function compose(Agent $agent, PromptTemplate $promptTemplate, string $outputKey = 'output'): ChainFactory
    {
        return ChainFactory::chain($agent, $promptTemplate, $outputKey);
    }

    public function then(ChainContract $chain): SequentialChain
    {
        return new SequentialChain([$this, $chain]);
    }

    public function withProvider(string|array|null $provider): self
    {
        $clone = clone $this;
        $clone->provider = $provider;

        return $clone;
    }

    public function withModel(?string $model): self
    {
        $clone = clone $this;
        $clone->model = $model;

        return $clone;
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

        $response = $this->agent->prompt(
            prompt: $prompt,
            provider: $this->provider,
            model: $this->model,
        );
        $raw = $this->extractResponseText($response);

        $this->memory?->add('human', $this->extractPrimaryInput($inputs));
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

        foreach ($this->agent->stream(
            prompt: $prompt,
            provider: $this->provider,
            model: $this->model,
        ) as $event) {
            if (is_object($event) && method_exists($event, 'text')) {
                yield (string) $event->text();

                continue;
            }

            if (is_object($event) && isset($event->delta) && is_string($event->delta)) {
                yield $event->delta;

                continue;
            }

            if (is_string($event)) {
                yield (string) $event;
            }
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
            $query = $this->extractPrimaryInput($inputs, requireInputKey: true);
            $docs = $this->retriever->retrieve($query, $this->topK);
            $inputs['context'] = implode("\n\n", array_map(fn ($d) => $d->content, $docs));
        }

        if ($this->memory !== null) {
            $inputs['history'] = $this->memory->asString();
        }

        return $inputs;
    }

    private function extractPrimaryInput(array $inputs, bool $requireInputKey = false): string
    {
        if (array_key_exists('input', $inputs)) {
            return $this->stringifyInputValue($inputs['input'], 'input');
        }

        if ($requireInputKey) {
            throw new InvalidArgumentException("Retriever-enabled chains require an 'input' key.");
        }

        if ($inputs === []) {
            return '';
        }

        $firstKey = array_key_first($inputs);
        $firstValue = $inputs[$firstKey];

        return $this->stringifyInputValue($firstValue, (string) $firstKey);
    }

    private function stringifyInputValue(mixed $value, string $sourceKey): string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        throw new InvalidArgumentException("Input value for key '{$sourceKey}' must be scalar or Stringable.");
    }

    private function extractResponseText(mixed $response): string
    {
        if (is_object($response) && isset($response->text) && is_string($response->text)) {
            return $response->text;
        }

        if (is_object($response) && method_exists($response, 'text')) {
            return (string) $response->text();
        }

        return (string) $response;
    }
}
