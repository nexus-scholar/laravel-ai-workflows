<?php

namespace NexusScholar\AiChain\Agents\Concerns;

use NexusScholar\AiChain\Contracts\Chain;
use NexusScholar\AiChain\Contracts\Memory;
use NexusScholar\AiChain\Contracts\Retriever;

trait HasChain
{
    protected Chain $chain;

    public function withChain(Chain $chain): self
    {
        $this->chain = $chain;
        return $this;
    }

    public function chain(): Chain
    {
        return $this->chain;
    }

    public function withMemory(Memory $memory): self
    {
        $this->chain = $this->chain->withMemory($memory);
        return $this;
    }

    public function withRetriever(Retriever $retriever, int $topK = 5): self
    {
        $this->chain = $this->chain->withRetriever($retriever, $topK);
        return $this;
    }
}
