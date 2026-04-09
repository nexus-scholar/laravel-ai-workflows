<?php

namespace Nexus\AiChain\Graph;

use InvalidArgumentException;
use Nexus\AiChain\Contracts\Node;
use Nexus\AiChain\Graph\Exceptions\GraphValidationException;

final class StateGraph
{
    public const string END = '__END__';

    private array $nodes = [];

    private array $edges = [];

    private ?string $entryPoint = null;

    public function addNode(string $name, Node|callable $node): self
    {
        if ($name === '') {
            throw new GraphValidationException('Node name cannot be empty.');
        }

        if (isset($this->nodes[$name])) {
            throw new InvalidArgumentException("Node '{$name}' already exists.");
        }

        $this->nodes[$name] = $node;

        return $this;
    }

    public function addEdge(string $from, string $to): self
    {
        if ($from === '' || $to === '') {
            throw new GraphValidationException('Edge source and destination cannot be empty.');
        }

        $this->edges[$from][] = Edge::direct($from, $to);

        return $this;
    }

    public function addConditionalEdge(string $from, callable $condition): self
    {
        if ($from === '') {
            throw new GraphValidationException('Conditional edge source cannot be empty.');
        }

        $this->edges[$from][] = Edge::conditional($from, $condition(...));

        return $this;
    }

    public function setEntryPoint(string $nodeName): self
    {
        if ($nodeName === '') {
            throw new GraphValidationException('Entry point cannot be empty.');
        }

        $this->entryPoint = $nodeName;

        return $this;
    }

    public function compile(): CompiledGraph
    {
        if ($this->nodes === []) {
            throw new GraphValidationException('Cannot compile an empty graph.');
        }

        if ($this->entryPoint === null) {
            throw new GraphValidationException('Entry point must be set before compiling.');
        }

        if (! isset($this->nodes[$this->entryPoint])) {
            throw new GraphValidationException("Entry point '{$this->entryPoint}' is not a registered node.");
        }

        foreach (array_keys($this->edges) as $from) {
            if (! isset($this->nodes[$from]) && $from !== self::END) {
                throw new GraphValidationException("Edge starts from non-existent node '{$from}'.");
            }
        }

        foreach ($this->edges as $from => $edges) {
            foreach ($edges as $edge) {
                /** @var Edge $edge */
                if ($edge->isConditional()) {
                    continue;
                }

                if ($edge->to === null) {
                    throw new GraphValidationException("Direct edge from '{$from}' must define a destination node.");
                }

                if ($edge->to !== self::END && ! isset($this->nodes[$edge->to])) {
                    throw new GraphValidationException("Edge points to non-existent node '{$edge->to}'.");
                }
            }
        }

        $compiled = new CompiledGraph($this->nodes, $this->edges, $this->entryPoint);

        $maxIterations = 50;

        if (function_exists('config')) {
            try {
                $configuredMaxIterations = (int) config('ai-chain.graph.max_iterations', 50);
                if ($configuredMaxIterations > 0) {
                    $maxIterations = $configuredMaxIterations;
                }
            } catch (\Throwable) {
                // Non-Laravel runtime: keep safe default.
            }
        }

        $compiled = $compiled->withMaxIterations($maxIterations);

        return $compiled;
    }
}
