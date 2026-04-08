<?php

namespace NexusScholar\AiChain\Retrieval;

use Laravel\Ai\Reranking;
use NexusScholar\AiChain\Contracts\Retriever;

final class RerankingRetriever implements Retriever
{
    public function __construct(
        private readonly Retriever $baseRetriever,
        private readonly int       $fetchK = 20,
        private readonly ?string   $provider = null,
        private readonly ?string   $model = null,
    ) {}

    public function retrieve(string $query, int $topK = 5): array
    {
        // Over-fetch then rerank
        $candidates = $this->baseRetriever->retrieve($query, $this->fetchK);
        
        if (empty($candidates)) {
            return [];
        }

        $contents = array_map(fn ($d) => $d->content, $candidates);

        $response = \Laravel\Ai\Reranking::of($contents)
            ->limit($topK)
            ->rerank($query, $this->provider, $this->model);

        // Map back to Documents with updated scores and original metadata if possible
        $result = [];
        foreach ($response->results as $r) {
            // Find original doc to preserve metadata
            $original = collect($candidates)->first(fn($d) => $d->content === $r->document);
            
            $result[] = new Document(
                content:  $r->document,
                metadata: $original ? $original->metadata : [],
                score:    $r->score,
            );
        }

        return $result;
    }
}
