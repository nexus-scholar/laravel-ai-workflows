<?php

namespace Nexus\Workflow\Retrieval;

use InvalidArgumentException;
use Laravel\Ai\Reranking;
use Nexus\Workflow\Contracts\Retriever;

final class RerankingRetriever implements Retriever
{
    public function __construct(
        private readonly Retriever $baseRetriever,
        private readonly int $fetchK = 20,
        private readonly ?string $provider = null,
        private readonly ?string $model = null,
    ) {
        if ($this->fetchK <= 0) {
            throw new InvalidArgumentException('fetchK must be greater than 0.');
        }
    }

    public function retrieve(string $query, int $topK = 5): array
    {
        if ($topK <= 0) {
            return [];
        }

        // Over-fetch then rerank
        $candidates = $this->baseRetriever->retrieve($query, max($this->fetchK, $topK));

        $normalizedCandidates = array_values(array_filter($candidates, function (mixed $candidate): bool {
            /** @phpstan-ignore instanceof.alwaysTrue */
            return $candidate instanceof Document && trim($candidate->content) !== '';
        }));

        if ($normalizedCandidates === []) {
            return [];
        }

        $contentToDocument = [];
        foreach ($normalizedCandidates as $doc) {
            if (! isset($contentToDocument[$doc->content])) {
                $contentToDocument[$doc->content] = $doc;
            }
        }

        $contents = array_keys($contentToDocument);

        $response = Reranking::of($contents)
            ->limit($topK)
            ->rerank($query, $this->provider, $this->model);

        // Map back to Documents with updated scores and original metadata if possible
        $result = [];
        foreach ($response->results as $r) {
            if (! isset($r->document) || ! is_string($r->document) || $r->document === '') {
                continue;
            }

            $original = $contentToDocument[$r->document] ?? null;

            $result[] = new Document(
                content: $r->document,
                metadata: $original ? $original->metadata : [],
                score: isset($r->score) && is_numeric($r->score) ? (float) $r->score : null,
            );

            if (count($result) >= $topK) {
                break;
            }
        }

        return $result;
    }
}
