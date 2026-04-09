<?php

declare(strict_types=1);

namespace Nexus\AiChain\Tests\Unit\Graph;

use Illuminate\Support\Facades\Cache;
use Nexus\AiChain\Graph\Checkpoint\CacheCheckpoint;
use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Tests\TestCase;

if (! class_exists(CounterState::class)) {
    class CounterState extends State
    {
        public function __construct(public int $count = 0) {}

        public function toArray(): array
        {
            return ['count' => $this->count];
        }

        public static function fromArray(array $data): static
        {
            return new self($data['count']);
        }
    }
}

class CacheCheckpointTest extends TestCase
{
    public function test_it_saves_and_loads_history_entries_with_expected_shape(): void
    {
        Cache::store('array')->flush();

        $checkpoint = new CacheCheckpoint(store: 'array', ttl: 600);

        $checkpoint->save('run_1', 'node_a', new CounterState(1));
        $checkpoint->save('run_1', 'node_b', new CounterState(2));

        $history = $checkpoint->load('run_1');

        $this->assertCount(2, $history);
        $this->assertSame('node_a', $history[0]['node']);
        $this->assertSame(['count' => 1], $history[0]['state']);
        $this->assertSame(CounterState::class, $history[0]['class']);
        $this->assertIsString($history[0]['timestamp']);
    }

    public function test_it_filters_malformed_history_entries_on_load(): void
    {
        Cache::store('array')->flush();

        Cache::store('array')->put('graph_run:run_2', [
            ['node' => 'ok', 'state' => ['count' => 1], 'class' => CounterState::class, 'timestamp' => now()->toIso8601String()],
            ['node' => 'missing_state', 'class' => CounterState::class, 'timestamp' => now()->toIso8601String()],
            'invalid',
            ['node' => 'bad_state', 'state' => 'oops', 'class' => CounterState::class, 'timestamp' => now()->toIso8601String()],
        ], 600);

        $checkpoint = new CacheCheckpoint(store: 'array', ttl: 600);
        $history = $checkpoint->load('run_2');

        $this->assertCount(1, $history);
        $this->assertSame('ok', $history[0]['node']);
    }

    public function test_latest_returns_last_valid_checkpoint_or_null(): void
    {
        Cache::store('array')->flush();

        $checkpoint = new CacheCheckpoint(store: 'array', ttl: 600);

        $this->assertNull($checkpoint->latest('run_empty'));

        $checkpoint->save('run_3', 'first', new CounterState(10));
        $checkpoint->save('run_3', 'second', new CounterState(20));

        $latest = $checkpoint->latest('run_3');

        $this->assertNotNull($latest);
        $this->assertSame('second', $latest['node']);
        $this->assertSame(['count' => 20], $latest['state']);
    }

    public function test_it_uses_forever_storage_when_ttl_is_non_positive(): void
    {
        Cache::shouldReceive('store')->twice()->with('file')->andReturnSelf();
        Cache::shouldReceive('get')->once()->with('graph_run:run_forever', [])->andReturn([]);
        Cache::shouldReceive('forever')->once();

        $checkpoint = new CacheCheckpoint(store: 'file', ttl: 0);
        $checkpoint->save('run_forever', 'start', new CounterState(1));
    }
}

