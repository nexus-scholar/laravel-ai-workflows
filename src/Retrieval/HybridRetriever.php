<?php

namespace Nexus\AiChain\Retrieval;

use Nexus\AiChain\Contracts\Retriever;

/**
 * Fuses two retrievers (typically vector + keyword) using Reciprocal Rank Fusion.
 * RRF score: 1/(k + rank) where k=60 is the standard constant.
 */
final class HybridRetriever implements Retriever
{
    private array $docMap = [];

    public function __construct(
        private readonly Retriever $vectorRetriever,
        private readonly Retriever $keywordRetriever,
        private readonly int $k = 60,
    ) {}

    public function retrieve(string $query, int $topK = 5): array
    {
        $vectorDocs = $this->vectorRetriever->retrieve($query, $topK * 2);
        $keywordDocs = $this->keywordRetriever->retrieve($query, $topK * 2);

        $scores = [];
        $this->docMap = [];

        foreach ([$vectorDocs, $keywordDocs] as $resultList) {
            foreach ($resultList as $rank => $doc) {
                $key = md5($doc->content);
                $scores[$key] = ($scores[$key] ?? 0) + 1 / ($this->k + $rank + 1);

                if (! isset($this->docMap[$key])) {
                    $this->docMap[$key] = $doc;
                }
            }
        }

        arsort($scores);

        $topKeys = array_slice(array_keys($scores), 0, $topK);

        return array_map(function ($key) use ($scores) {
            return $this->docMap[$key]->withScore((float) $scores[$key]);
        }, $topKeys);
    }
}
