<?php

namespace NexusScholar\AiChain\Contracts;

interface Retriever
{
    /**
     * @return \NexusScholar\AiChain\Retrieval\Document[]
     */
    public function retrieve(string $query, int $topK = 5): array;
}
