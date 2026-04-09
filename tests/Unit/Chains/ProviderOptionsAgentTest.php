<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Nexus\Workflow\Chains\Support\ProviderOptionsAgent;
use Nexus\Workflow\Chains\Support\StructuredProviderOptionsAgent;

use function Laravel\Ai\agent;

it('merges base, wildcard and provider-specific options', function () {
    $inner = new class ('instructions', [], []) extends \Laravel\Ai\AnonymousAgent implements HasProviderOptions
    {
        public function providerOptions(\Laravel\Ai\Enums\Lab|string $provider): array
        {
            return ['temperature' => 0.1, 'base' => true];
        }
    };

    $agent = new ProviderOptionsAgent(
        $inner,
        [
            '*' => ['top_p' => 0.9, 'temperature' => 0.2],
            'openai' => ['temperature' => 0.4],
        ],
    );

    expect($agent->providerOptions('openai'))->toBe([
        'temperature' => 0.4,
        'base' => true,
        'top_p' => 0.9,
    ]);
});

it('applies provider options resolver as highest precedence', function () {
    $inner = agent(instructions: 'test');

    $agent = new ProviderOptionsAgent(
        $inner,
        ['openai' => ['temperature' => 0.2]],
        function ($provider, array $options): array {
            expect($provider)->toBe('openai');
            expect($options['temperature'])->toBe(0.2);

            return ['temperature' => 0.6, 'reasoning' => ['effort' => 'low']];
        },
    );

    expect($agent->providerOptions('openai'))->toBe([
        'temperature' => 0.6,
        'reasoning' => ['effort' => 'low'],
    ]);
});

it('delegates structured schema in structured wrapper', function () {
    $inner = agent(
        instructions: 'structured',
        schema: fn ($schema) => ['summary' => $schema->string()->required()],
    );

    $agent = new StructuredProviderOptionsAgent($inner);

    $schema = $agent->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKey('summary');
});

it('forwards tool declarations from the inner agent', function () {
    $tool = new class implements Tool
    {
        public bool $invoked = false;

        public function description(): string
        {
            return 'demo';
        }

        public function handle(Request $request): string
        {
            $this->invoked = true;

            return 'ok';
        }

        /**
         * @return array<string, Type>
         */
        public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
        {
            return ['input' => $schema->string()->required()];
        }
    };

    $inner = new class ($tool) extends \Laravel\Ai\AnonymousAgent implements HasTools
    {
        public function __construct(private readonly Tool $tool)
        {
            parent::__construct('instructions', [], []);
        }

        public function tools(): iterable
        {
            return [$this->tool];
        }
    };

    $agent = new ProviderOptionsAgent($inner);
    $tools = iterator_to_array($agent->tools());

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBe($tool)
        ->and($tool->invoked)->toBeFalse();
});

