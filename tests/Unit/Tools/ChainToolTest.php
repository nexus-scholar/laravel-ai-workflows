<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Tools;

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Tools\Request;
use Mockery;
use Nexus\Workflow\Contracts\Chain;
use Nexus\Workflow\Tests\TestCase;
use Nexus\Workflow\Tools\ChainTool;

class ChainToolTest extends TestCase
{
    public function test_it_delegates_to_chain()
    {
        $chain = Mockery::mock(Chain::class);
        $chain->shouldReceive('run')->once()->with(['input' => 'test'])->andReturn('output');
        $chain->shouldReceive('inputKeys')->andReturn(['input']);

        $tool = new ChainTool($chain, 'my_tool', 'description');

        $request = new Request(['input' => 'test']);
        $result = $tool->handle($request);

        expect((string) $result)->toBe('output');
    }

    public function test_it_exposes_schema_from_chain_inputs()
    {
        $chain = Mockery::mock(Chain::class);
        $chain->shouldReceive('inputKeys')->andReturn(['foo', 'bar']);

        $tool = new ChainTool($chain, 'my_tool', 'description');

        $schema = $tool->schema(new JsonSchemaTypeFactory);

        expect($schema)->toHaveKeys(['foo', 'bar']);
        expect($schema['foo']->toArray()['type'])->toBe('string');
    }

    public function test_it_serializes_array_results_as_json()
    {
        $chain = Mockery::mock(Chain::class);
        $chain->shouldReceive('run')->once()->with(['input' => 'test'])->andReturn(['ok' => true, 'count' => 2]);
        $chain->shouldReceive('inputKeys')->andReturn(['input']);

        $tool = new ChainTool($chain, 'my_tool', 'description');

        $request = new Request(['input' => 'test']);
        $result = $tool->handle($request);

        expect((string) $result)->toBe('{"ok":true,"count":2}');
    }
}
