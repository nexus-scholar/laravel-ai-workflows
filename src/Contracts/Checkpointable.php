<?php

namespace Nexus\AiChain\Contracts;

use Nexus\AiChain\Graph\State;

interface Checkpointable
{
    public function save(string $runId, string $nodeName, State $state): void;

    /**
     * @return array<int, array{node: string, state: array, class: string, timestamp: string}>
     */
    public function load(string $runId): array;

    /**
     * @return array{node: string, state: array, class: string, timestamp: string}|null
     */
    public function latest(string $runId): ?array;
}
