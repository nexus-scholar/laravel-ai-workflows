<?php

namespace Nexus\AiChain\Graph\Checkpoint;

use Illuminate\Support\Facades\Cache;
use Nexus\AiChain\Contracts\Checkpointable;
use Nexus\AiChain\Graph\State;

final class CacheCheckpoint implements Checkpointable
{
    public function __construct(
        private readonly string $store = 'file',
        private readonly int $ttl = 86400,
    ) {}

    public function save(string $runId, string $nodeName, State $state): void
    {
        $history = $this->load($runId);

        $history[] = [
            'node' => $nodeName,
            'state' => $state->toArray(),
            'class' => get_class($state),
            'timestamp' => now()->toIso8601String(),
        ];

        Cache::store($this->store)->put($this->cacheKey($runId), $history, $this->ttl);
    }

    public function load(string $runId): array
    {
        return Cache::store($this->store)->get($this->cacheKey($runId), []);
    }

    public function latest(string $runId): ?array
    {
        $history = $this->load($runId);

        return empty($history) ? null : end($history);
    }

    private function cacheKey(string $runId): string
    {
        return "graph_run:{$runId}";
    }
}
