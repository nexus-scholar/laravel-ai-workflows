<?php

namespace Nexus\Workflow\Chains;

use InvalidArgumentException;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Nexus\Workflow\Chains\Support\ProviderOptionsAgent;
use Nexus\Workflow\Chains\Support\StructuredProviderOptionsAgent;
use Nexus\Workflow\Contracts\Chain as ChainContract;
use Nexus\Workflow\Contracts\Memory;
use Nexus\Workflow\Contracts\Retriever;
use Nexus\Workflow\Prompts\PromptTemplate;
use Stringable;

final class Chain implements ChainContract
{
    /**
     * Boundary note: this class is orchestration-only.
     *
     * It composes prompt data and forwards execution to Laravel AI agents.
     * Provider transport concerns (HTTP clients, request signing, retries) must stay in the SDK/provider layer.
     */
    private ?Memory $memory = null;

    private ?Retriever $retriever = null;

    private int $topK = 5;

    private string|array|null $provider = null;

    private ?string $model = null;

    private ?int $timeout = null;

    /**
     * @var array<int, mixed>
     */
    private array $attachments = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $providerOptions = [];

    /**
     * @var (callable(Lab|string, array<string, mixed>, Agent): array<string, mixed>)|null
     */
    private $providerOptionsResolver = null;

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

    public function withTimeout(?int $timeout): self
    {
        $clone = clone $this;
        $clone->timeout = $timeout;

        return $clone;
    }

    /**
     * @param  array<int, mixed>  $attachments
     */
    public function withAttachments(array $attachments): self
    {
        $clone = clone $this;
        $clone->attachments = $attachments;

        return $clone;
    }

    /**
     * @param  array<string, array<string, mixed>>  $providerOptions
     */
    public function withProviderOptions(array $providerOptions): self
    {
        $clone = clone $this;
        $clone->providerOptions = $providerOptions;

        return $clone;
    }

    /**
     * @param  callable(Lab|string, array<string, mixed>, Agent): array<string, mixed>  $resolver
     */
    public function withProviderOptionsResolver(callable $resolver): self
    {
        $clone = clone $this;
        $clone->providerOptionsResolver = $resolver;

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
        $agent = $this->resolveAgent();
        $augmented = $this->augmentInputs($inputs);
        $prompt = $this->promptTemplate->format($augmented);

        $response = $this->invokeAgentPrompt($agent, $prompt);
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
        foreach ($this->streamEvents($inputs) as $event) {
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

    public function streamEvents(array $inputs): iterable
    {
        $agent = $this->resolveAgent();
        $augmented = $this->augmentInputs($inputs);
        $prompt = $this->promptTemplate->format($augmented);

        return $this->invokeAgentStream($agent, $prompt);
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

    private function resolveAgent(): Agent
    {
        if ($this->providerOptions === [] && $this->providerOptionsResolver === null) {
            return $this->agent;
        }

        if ($this->agent instanceof HasStructuredOutput) {
            return new StructuredProviderOptionsAgent(
                $this->agent,
                $this->providerOptions,
                $this->providerOptionsResolver,
            );
        }

        return new ProviderOptionsAgent(
            $this->agent,
            $this->providerOptions,
            $this->providerOptionsResolver,
        );
    }

    private function invokeAgentPrompt(Agent $agent, string $prompt): mixed
    {
        $args = [$prompt, $this->attachments, $this->provider, $this->model];

        if ($this->agentMethodSupportsTimeout($agent, 'prompt')) {
            $args[] = $this->timeout;
        }

        return $agent->prompt(...$args);
    }

    private function invokeAgentStream(Agent $agent, string $prompt): iterable
    {
        $args = [$prompt, $this->attachments, $this->provider, $this->model];

        if ($this->agentMethodSupportsTimeout($agent, 'stream')) {
            $args[] = $this->timeout;
        }

        return $agent->stream(...$args);
    }

    private function agentMethodSupportsTimeout(Agent $agent, string $method): bool
    {
        $reflection = new \ReflectionMethod($agent, $method);

        return $reflection->getNumberOfParameters() >= 5;
    }
}
