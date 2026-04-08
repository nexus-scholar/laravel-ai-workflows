<?php

namespace NexusScholar\AiChain\Contracts;

use NexusScholar\AiChain\Graph\State;

interface Checkpointable
{
    public function save(string $runId, string $nodeName, State $state): void;

    public function load(string $runId): ?array;

    public function latest(string $runId): ?array;
}
