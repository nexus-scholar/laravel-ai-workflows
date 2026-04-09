<?php

namespace Nexus\Workflow\Graph\Checkpoint;

use Illuminate\Support\Facades\Cache;
use Nexus\Workflow\Contracts\Checkpointable;
use Nexus\Workflow\Graph\State;

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

        $store = Cache::store($this->store);

        if ($this->ttl <= 0) {
            $store->forever($this->cacheKey($runId), $history);

            return;
        }

        $store->put($this->cacheKey($runId), $history, $this->ttl);
    }

    public function load(string $runId): array
    {
        $history = Cache::store($this->store)->get($this->cacheKey($runId), []);

        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter($history, function (mixed $entry): bool {
            if (! is_array($entry)) {
                return false;
            }

            return isset($entry['node'], $entry['state'], $entry['class'], $entry['timestamp'])
                && is_string($entry['node'])
                && is_array($entry['state'])
                && is_string($entry['class'])
                && is_string($entry['timestamp']);
        }));
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
