<?php

namespace Nexus\Workflow\Chains\Support;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;

final class StructuredProviderOptionsAgent extends ProviderOptionsAgent implements HasStructuredOutput
{
    // Structured wrapper boundary: preserve schema behavior while delegating all transport to SDK internals.

    /**
     * @param  Agent&HasStructuredOutput  $agent
     * @param  array<string, array<string, mixed>>  $providerOptions
     * @param  (callable(Lab|string, array<string, mixed>, Agent): array<string, mixed>)|null  $providerOptionsResolver
     */
    public function __construct(
        Agent $agent,
        array $providerOptions = [],
        ?callable $providerOptionsResolver = null,
    ) {
        parent::__construct($agent, $providerOptions, $providerOptionsResolver);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->structuredAgent()->schema($schema);
    }

    /**
     * @return Agent&HasStructuredOutput
     */
    private function structuredAgent(): Agent
    {
        if (! $this->agent instanceof HasStructuredOutput) {
            throw new \LogicException('Structured provider options agent requires an inner agent with structured output support.');
        }

        return $this->agent;
    }
}
