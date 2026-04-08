<?php

namespace NexusScholar\AiChain\Graph;

use InvalidArgumentException;
use NexusScholar\AiChain\Contracts\Node;

final class StateGraph
{
    public const END = '__END__';

    private array  $nodes      = [];
    private array  $edges      = [];
    private ?string $entryPoint = null;

    public function addNode(string $name, Node|callable $node): self
    {
        if (isset($this->nodes[$name])) {
            throw new InvalidArgumentException("Node '{$name}' already exists.");
        }

        $this->nodes[$name] = $node;
        return $this;
    }

    public function addEdge(string $from, string $to): self
    {
        $this->edges[$from][] = Edge::direct($from, $to);
        return $this;
    }

    public function addConditionalEdge(string $from, callable $condition): self
    {
        $this->edges[$from][] = Edge::conditional($from, $condition(...));
        return $this;
    }

    public function setEntryPoint(string $nodeName): self
    {
        $this->entryPoint = $nodeName;
        return $this;
    }

    public function compile(): CompiledGraph
    {
        if ($this->entryPoint === null) {
            throw new InvalidArgumentException("Entry point must be set before compiling.");
        }

        foreach (array_keys($this->edges) as $from) {
            if (!isset($this->nodes[$from]) && $from !== self::END) {
                throw new InvalidArgumentException("Edge starts from non-existent node '{$from}'.");
            }
        }

        return new CompiledGraph($this->nodes, $this->edges, $this->entryPoint);
    }
}
