<?php

namespace NexusScholar\AiChain\Contracts;

use NexusScholar\AiChain\Graph\State;

interface Node
{
    /**
     * Process the current state and return an updated state.
     * Nodes are pure functions — never mutate the incoming state directly.
     */
    public function handle(State $state): State;

    /**
     * Human-readable name for this node (used in logs and checkpoints).
     */
    public function name(): string;
}
