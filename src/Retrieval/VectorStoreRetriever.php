<?php

namespace Nexus\AiChain\Retrieval;

use Nexus\AiChain\Contracts\Retriever;

final class VectorStoreRetriever implements Retriever
{
    /**
     * @param  \Closure(string, int): array  $searcher  A closure that receives ($query, $topK) and returns results.
     */
    public function __construct(
        private readonly \Closure $searcher,
    ) {}

    public function retrieve(string $query, int $topK = 5): array
    {
        $results = ($this->searcher)($query, $topK);

        return array_map(function ($r) {
            // Support both array and object results
            $content = is_array($r) ? ($r['content'] ?? '') : ($r->content ?? '');
            $metadata = is_array($r) ? ($r['metadata'] ?? []) : ($r->metadata ?? []);
            $score = is_array($r) ? ($r['score'] ?? null) : ($r->score ?? null);

            return new Document(
                content: (string) $content,
                metadata: (array) $metadata,
                score: $score !== null ? (float) $score : null,
            );
        }, $results);
    }
}
