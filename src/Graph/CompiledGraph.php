<?php

namespace Nexus\AiChain\Graph;

use Generator;
use InvalidArgumentException;
use Nexus\AiChain\Contracts\Checkpointable;
use Nexus\AiChain\Contracts\Node;
use RuntimeException;

final class CompiledGraph
{
    private ?Checkpointable $checkpoint = null;

    private int $maxIterations = 50;

    public function __construct(
        private readonly array $nodes,
        private readonly array $edges,
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
        if ($max <= 0) {
            throw new InvalidArgumentException('Max iterations must be greater than 0.');
        }

        $clone = clone $this;
        $clone->maxIterations = $max;

        return $clone;
    }

    public function invoke(State $initialState, ?string $runId = null): State
    {
        $state = $initialState;
        $currentNode = $this->entryPoint;
        $iterations = 0;

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
        $state = $initialState;
        $currentNode = $this->entryPoint;
        $iterations = 0;

        while ($currentNode !== StateGraph::END) {
            if ($iterations >= $this->maxIterations) {
                throw new RuntimeException('Max iterations reached.');
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

    public function executeNode(string $name, State $state): State
    {
        if (! isset($this->nodes[$name])) {
            throw new RuntimeException("Node '{$name}' is not defined in this graph.");
        }

        $node = $this->nodes[$name];

        $result = $node instanceof Node
            ? $node->handle($state)
            : $node($state);

        if (! $result instanceof State) {
            throw new RuntimeException("Node '{$name}' must return an instance of ".State::class.'.');
        }

        return $result;
    }

    public function resolveNextNode(string $currentNode, State $state): string
    {
        $edges = $this->edges[$currentNode] ?? [];

        foreach ($edges as $edge) {
            /** @var Edge $edge */
            if ($edge->isConditional()) {
                $result = ($edge->condition)($state);
                if ($result !== null) {
                    if (! is_string($result)) {
                        throw new RuntimeException("Conditional edge from '{$currentNode}' must return a string node name or null.");
                    }

                    if ($result !== StateGraph::END && ! isset($this->nodes[$result])) {
                        throw new RuntimeException("Conditional edge from '{$currentNode}' routed to unknown node '{$result}'.");
                    }

                    return $result;
                }
            } else {
                if ($edge->to === null) {
                    throw new RuntimeException("Direct edge from '{$currentNode}' has no destination.");
                }

                if ($edge->to !== StateGraph::END && ! isset($this->nodes[$edge->to])) {
                    throw new RuntimeException("Direct edge from '{$currentNode}' points to unknown node '{$edge->to}'.");
                }

                return $edge->to;
            }
        }

        return StateGraph::END;
    }

    public function entryPoint(): string
    {
        return $this->entryPoint;
    }

    public function checkpoint(): ?Checkpointable
    {
        return $this->checkpoint;
    }

    /**
     * Determine whether this compiled graph can be safely serialized in a queued job payload.
     */
    public function isQueueSafe(): bool
    {
        return $this->queueSafetyIssues() === [];
    }

    /**
     * Return queue-safety issues found in the graph definition.
     *
     * @return string[]
     */
    public function queueSafetyIssues(): array
    {
        $issues = [];

        foreach ($this->nodes as $name => $node) {
            if (! $node instanceof Node) {
                $issues[] = "Node '{$name}' is a callable/closure and cannot be serialized safely for queued execution.";
            }
        }

        foreach ($this->edges as $from => $edges) {
            foreach ($edges as $edge) {
                /** @var Edge $edge */
                if ($edge->isConditional()) {
                    $issues[] = "Conditional edge from '{$from}' uses a closure and is not queue-safe by serialization.";
                }
            }
        }

        return array_values(array_unique($issues));
    }
}
