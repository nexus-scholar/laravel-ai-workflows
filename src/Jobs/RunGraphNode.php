<?php

namespace Nexus\AiChain\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Nexus\AiChain\Graph\CompiledGraph;
use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Graph\StateGraph;
use RuntimeException;

class RunGraphNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ?CompiledGraph $graph,
        private readonly State $state,
        private readonly string $currentNode,
        private readonly string $runId,
        private readonly ?string $graphResolver = null,
    ) {}

    public function handle(): void
    {
        $graph = $this->resolveGraph();

        $newState = $graph->executeNode($this->currentNode, $this->state);

        if ($checkpoint = $graph->checkpoint()) {
            $checkpoint->save($this->runId, $this->currentNode, $newState);
        }

        $nextNode = $graph->resolveNextNode($this->currentNode, $newState);

        if ($nextNode !== StateGraph::END) {
            dispatch(new self(
                $this->graphResolver ? null : $graph,
                $newState,
                $nextNode,
                $this->runId,
                $this->graphResolver,
            ));
        }
    }

    private function resolveGraph(): CompiledGraph
    {
        if ($this->graphResolver !== null) {
            $resolved = App::make($this->graphResolver);

            if (! $resolved instanceof CompiledGraph) {
                throw new RuntimeException("Graph resolver '{$this->graphResolver}' must resolve to ".CompiledGraph::class.'.');
            }

            return $resolved;
        }

        if ($this->graph === null) {
            throw new RuntimeException('No graph instance available for queued node execution.');
        }

        return $this->graph;
    }
}
