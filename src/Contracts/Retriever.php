<?php

namespace Nexus\AiChain\Contracts;

use Nexus\AiChain\Retrieval\Document;

interface Retriever
{
    /**
     * @return Document[]
     */
    public function retrieve(string $query, int $topK = 5): array;
}
