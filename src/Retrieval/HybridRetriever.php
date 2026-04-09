<?php

namespace Nexus\Workflow\Retrieval;

use InvalidArgumentException;
use Nexus\Workflow\Contracts\Retriever;

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
    ) {
        if ($this->k < 0) {
            throw new InvalidArgumentException('RRF constant k must be greater than or equal to 0.');
        }
    }

    public function retrieve(string $query, int $topK = 5): array
    {
        if ($topK <= 0) {
            return [];
        }

        $vectorDocs = $this->vectorRetriever->retrieve($query, $topK * 2);
        $keywordDocs = $this->keywordRetriever->retrieve($query, $topK * 2);

        $scores = [];
        $this->docMap = [];

        foreach ([$vectorDocs, $keywordDocs] as $resultList) {
            foreach ($resultList as $rank => $doc) {
                /** @phpstan-ignore instanceof.alwaysTrue */
                if (! $doc instanceof Document) {
                    continue;
                }

                $content = trim($doc->content);
                if ($content === '') {
                    continue;
                }

                $key = md5(strtolower($content));
                $scores[$key] = ($scores[$key] ?? 0) + 1 / ($this->k + $rank + 1);

                if (! isset($this->docMap[$key])) {
                    $this->docMap[$key] = new Document($content, $doc->metadata, $doc->score);
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
