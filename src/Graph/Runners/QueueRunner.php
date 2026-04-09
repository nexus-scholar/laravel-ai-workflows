<?php

namespace Nexus\AiChain\Graph\Runners;

use Illuminate\Support\Str;
use Nexus\AiChain\Graph\CompiledGraph;
use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Jobs\RunGraphNode;
use RuntimeException;

final class QueueRunner
{
    public function __construct(
        private readonly CompiledGraph $graph,
        private readonly ?string $graphResolver = null,
    ) {}

    public function dispatch(State $initialState, ?string $runId = null): string
    {
        $runId ??= (string) Str::uuid();

        if ($this->graphResolver === null && ! $this->graph->isQueueSafe()) {
            throw new RuntimeException(
                'Graph is not queue-safe for serialization. Use QueueRunner with a graph resolver key. Issues: '
                .implode(' | ', $this->graph->queueSafetyIssues())
            );
        }

        dispatch(new RunGraphNode(
            $this->graphResolver ? null : $this->graph,
            $initialState,
            $this->graph->entryPoint(),
            $runId,
            $this->graphResolver,
        ));

        return $runId;
    }
}
