<?php

declare(strict_types=1);

namespace NexusScholar\AiChain\Tests\Unit\Retrieval;

use Mockery;
use Laravel\Ai\Store;
use Laravel\Ai\Responses\Data\SearchResult;
use NexusScholar\AiChain\Retrieval\VectorStoreRetriever;
use NexusScholar\AiChain\Tests\TestCase;

class VectorStoreRetrieverTest extends TestCase
{
    public function test_it_maps_results_to_documents()
    {
        $searcher = function (string $query, int $topK) {
            return [
                ['content' => 'content 1', 'score' => 0.9, 'metadata' => ['id' => 1]],
                (object) ['content' => 'content 2', 'score' => 0.8, 'metadata' => ['id' => 2]],
            ];
        };

        $retriever = new VectorStoreRetriever($searcher);
        $results = $retriever->retrieve('query', 2);

        expect($results)->toHaveCount(2);
        expect($results[0]->content)->toBe('content 1');
        expect($results[0]->score)->toBe(0.9);
        expect($results[0]->metadata)->toBe(['id' => 1]);
        
        expect($results[1]->content)->toBe('content 2');
        expect($results[1]->score)->toBe(0.8);
        expect($results[1]->metadata)->toBe(['id' => 2]);
    }
}
