<?php

namespace NexusScholar\AiChain\Graph;

use Generator;
use RuntimeException;
use NexusScholar\AiChain\Contracts\Node;
use NexusScholar\AiChain\Contracts\Checkpointable;

final class CompiledGraph
{
    private ?Checkpointable $checkpoint    = null;
    private int             $maxIterations = 50;

    public function __construct(
        private readonly array  $nodes,
        private readonly array  $edges,
        private readonly string $entryPoint,
    ) {}

    public function withCheckpoint(Checkpointable $checkpoint): self
    {
        $clone = clone $this;
        $clone->checkpoint = $checkpoint;
        return $clone;
    }

    public function withMaxIterations(int $max): self
    {
        $clone = clone $this;
        $clone->maxIterations = $max;
        return $clone;
    }

    public function invoke(State $initialState, ?string $runId = null): State
    {
        $state       = $initialState;
        $currentNode = $this->entryPoint;
        $iterations  = 0;

        while ($currentNode !== StateGraph::END) {
            if ($iterations >= $this->maxIterations) {
                throw new RuntimeException("Max iterations ({$this->maxIterations}) reached in graph execution.");
            }

            $state = $this->executeNode($currentNode, $state);
            
            if ($this->checkpoint && $runId) {
                $this->checkpoint->save($runId, $currentNode, $state);
            }

            $currentNode = $this->resolveNextNode($currentNode, $state);
            $iterations++;
        }

        return $state;
    }

    public function stream(State $initialState, ?string $runId = null): Generator
    {
        $state       = $initialState;
        $currentNode = $this->entryPoint;
        $iterations  = 0;

        while ($currentNode !== StateGraph::END) {
            if ($iterations >= $this->maxIterations) {
                throw new RuntimeException("Max iterations reached.");
            }

            $state = $this->executeNode($currentNode, $state);
            
            if ($this->checkpoint && $runId) {
                $this->checkpoint->save($runId, $currentNode, $state);
            }

            yield $currentNode => $state;

            $currentNode = $this->resolveNextNode($currentNode, $state);
            $iterations++;
        }
    }

    private function executeNode(string $name, State $state): State
    {
        $node = $this->nodes[$name];

        if ($node instanceof Node) {
            return $node->handle($state);
        }

        return $node($state);
    }

    private function resolveNextNode(string $currentNode, State $state): string
    {
        $edges = $this->edges[$currentNode] ?? [];

        foreach ($edges as $edge) {
            /** @var Edge $edge */
            if ($edge->isConditional()) {
                $result = ($edge->condition)($state);
                if ($result !== null) {
                    return $result;
                }
            } else {
                return $edge->to;
            }
        }

        return StateGraph::END;
    }
}
