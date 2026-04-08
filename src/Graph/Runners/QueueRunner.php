<?php

namespace Nexus\AiChain\Graph\Runners;

use Illuminate\Support\Str;
use Nexus\AiChain\Graph\CompiledGraph;
use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Jobs\RunGraphNode;

final class QueueRunner
{
    public function __construct(
        private readonly CompiledGraph $graph
    ) {}

    public function dispatch(State $initialState, ?string $runId = null): string
    {
        $runId ??= (string) Str::uuid();

        dispatch(new RunGraphNode(
            $this->graph,
            $initialState,
            $this->graph->entryPoint(),
            $runId
        ));

        return $runId;
    }
}
