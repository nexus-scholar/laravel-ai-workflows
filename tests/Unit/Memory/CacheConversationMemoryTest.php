<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Memory;

use Illuminate\Support\Facades\Cache;
use Nexus\Workflow\Memory\CacheConversationMemory;
use Nexus\Workflow\Tests\TestCase;

class CacheConversationMemoryTest extends TestCase
{
    public function test_it_persists_to_cache()
    {
        Cache::shouldReceive('store')->andReturnSelf();
        Cache::shouldReceive('get')->once()->with('ai_memory:session_1', [])->andReturn([]);
        Cache::shouldReceive('put')->once()->with('ai_memory:session_1', [['role' => 'human', 'content' => 'Hi']], 3600);

        $memory = new CacheConversationMemory('session_1', store: 'file');
        $memory->add('human', 'Hi');
    }

    public function test_it_respects_sliding_window()
    {
        Cache::shouldReceive('store')->andReturnSelf();
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->atLeast()->once();

        $memory = new CacheConversationMemory('session_2', maxMessages: 2, store: 'file');
        $memory->add('human', '1');
        $memory->add('ai', '2');
        $memory->add('human', '3');

        expect($memory->messages())->toHaveCount(2);
        expect($memory->messages()[0]['content'])->toBe('2');
        expect($memory->messages()[1]['content'])->toBe('3');
    }

    public function test_it_normalizes_malformed_cached_messages()
    {
        Cache::shouldReceive('store')->andReturnSelf();
        Cache::shouldReceive('get')->once()->with('ai_memory:session_3', [])->andReturn([
            ['role' => 'human', 'content' => 'ok'],
            ['role' => 'ai'],
            'invalid',
        ]);

        $memory = new CacheConversationMemory('session_3', store: 'file');

        expect($memory->messages())->toHaveCount(1);
        expect($memory->messages()[0])->toBe(['role' => 'human', 'content' => 'ok']);
    }

    public function test_it_uses_forever_cache_when_ttl_is_non_positive()
    {
        Cache::shouldReceive('store')->twice()->andReturnSelf();
        Cache::shouldReceive('get')->once()->with('ai_memory:session_4', [])->andReturn([]);
        Cache::shouldReceive('forever')->once()->with('ai_memory:session_4', [['role' => 'human', 'content' => 'Hi']]);

        $memory = new CacheConversationMemory('session_4', store: 'file', ttl: 0);
        $memory->add('human', 'Hi');
    }
}
