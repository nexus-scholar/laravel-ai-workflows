<?php

declare(strict_types=1);

namespace Nexus\AiChain\Tests\Unit\Retrieval;

use Nexus\AiChain\Retrieval\VectorStoreRetriever;
use Nexus\AiChain\Tests\TestCase;

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

    public function test_it_filters_empty_or_malformed_results()
    {
        $searcher = function () {
            return [
                ['content' => ''],
                ['content' => '  valid  ', 'metadata' => 'bad_meta', 'score' => 'bad_score'],
                (object) ['content' => 'also valid', 'score' => 0.5],
            ];
        };

        $retriever = new VectorStoreRetriever($searcher);
        $results = $retriever->retrieve('query', 5);

        expect($results)->toHaveCount(2);
        expect($results[0]->content)->toBe('valid');
        expect($results[0]->metadata)->toBe([]);
        expect($results[0]->score)->toBeNull();
    }

    public function test_it_returns_empty_for_non_positive_top_k()
    {
        $searcher = fn () => [['content' => 'x']];
        $retriever = new VectorStoreRetriever($searcher);

        expect($retriever->retrieve('query', 0))->toBe([]);
    }
}
