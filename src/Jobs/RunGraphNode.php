<?php

namespace Nexus\AiChain\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexus\AiChain\Graph\CompiledGraph;
use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Graph\StateGraph;

class RunGraphNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly CompiledGraph $graph,
        private readonly State $state,
        private readonly string $currentNode,
        private readonly string $runId,
    ) {}

    public function handle(): void
    {
        $newState = $this->graph->executeNode($this->currentNode, $this->state);

        if ($checkpoint = $this->graph->checkpoint()) {
            $checkpoint->save($this->runId, $this->currentNode, $newState);
        }

        $nextNode = $this->graph->resolveNextNode($this->currentNode, $newState);

        if ($nextNode !== StateGraph::END) {
            dispatch(new self(
                $this->graph,
                $newState,
                $nextNode,
                $this->runId
            ));
        }
    }
}
