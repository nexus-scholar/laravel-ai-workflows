<?php

declare(strict_types=1);

namespace NexusScholar\AiChain\Tests\Unit\Memory;

use Illuminate\Support\Facades\Cache;
use NexusScholar\AiChain\Memory\CacheConversationMemory;
use NexusScholar\AiChain\Tests\TestCase;

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
}
