<?php

namespace Nexus\Workflow\Chains\Support;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class ProviderOptionsAgent implements Agent, Conversational, HasMiddleware, HasProviderOptions, HasTools
{
    use Promptable;

    // Wrapper boundary: extend agent metadata/options, never bypass SDK transport.

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $providerOptions;

    /**
     * @var (callable(Lab|string, array<string, mixed>, Agent): array<string, mixed>)|null
     */
    protected $providerOptionsResolver;

    /**
     * @param  array<string, array<string, mixed>>  $providerOptions
     * @param  (callable(Lab|string, array<string, mixed>, Agent): array<string, mixed>)|null  $providerOptionsResolver
     */
    public function __construct(
        protected readonly Agent $agent,
        array $providerOptions = [],
        ?callable $providerOptionsResolver = null,
    ) {
        $this->providerOptions = $providerOptions;
        $this->providerOptionsResolver = $providerOptionsResolver;
    }

    public function instructions(): string|\Stringable
    {
        return $this->agent->instructions();
    }

    public function messages(): iterable
    {
        if (! $this->agent instanceof Conversational) {
            return [];
        }

        return $this->agent->messages();
    }

    public function tools(): iterable
    {
        if (! $this->agent instanceof HasTools) {
            return [];
        }

        return $this->agent->tools();
    }

    /**
     * @return array<int, mixed>
     */
    public function middleware(): array
    {
        if (! $this->agent instanceof HasMiddleware) {
            return [];
        }

        return $this->agent->middleware();
    }

    public function providerOptions(Lab|string $provider): array
    {
        $providerName = $provider instanceof Lab ? $provider->value : $provider;

        $options = [];

        if ($this->agent instanceof HasProviderOptions) {
            $options = $this->agent->providerOptions($provider);
        }

        $options = array_replace(
            $options,
            $this->providerOptions['*'] ?? [],
            $this->providerOptions[$providerName] ?? [],
        );

        if ($this->providerOptionsResolver !== null) {
            $resolved = ($this->providerOptionsResolver)($provider, $options, $this->agent);


            $options = array_replace($options, $resolved);
        }

        return $options;
    }
}
