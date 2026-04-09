<?php

namespace Nexus\Workflow\Contracts;

use Nexus\Workflow\Retrieval\Document;

interface Retriever
{
    /**
     * @return Document[]
     */
    public function retrieve(string $query, int $topK = 5): array;
}
