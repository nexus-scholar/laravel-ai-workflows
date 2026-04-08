<?php

declare(strict_types=1);

namespace Nexus\AiChain\Tests\Unit\Retrieval;

use Laravel\Ai\Reranking;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\RankedDocument;
use Laravel\Ai\Responses\RerankingResponse;
use Mockery;
use Nexus\AiChain\Contracts\Retriever;
use Nexus\AiChain\Retrieval\Document;
use Nexus\AiChain\Retrieval\RerankingRetriever;
use Nexus\AiChain\Tests\TestCase;

class RerankingRetrieverTest extends TestCase
{
    public function test_it_reranks_documents()
    {
        $base = Mockery::mock(Retriever::class);
        $base->shouldReceive('retrieve')->andReturn([
            new Document('doc 1', ['id' => 1]),
            new Document('doc 2', ['id' => 2]),
        ]);

        Reranking::fake([
            new RerankingResponse([
                new RankedDocument(1, 'doc 2', 0.95),
                new RankedDocument(0, 'doc 1', 0.85),
            ], new Meta('provider', 'model')),
        ]);

        $retriever = new RerankingRetriever($base);
        $results = $retriever->retrieve('query', 2);

        expect($results)->toHaveCount(2);
        expect($results[0]->content)->toBe('doc 2');
        expect($results[0]->score)->toBe(0.95);
        expect($results[0]->metadata)->toBe(['id' => 2]);
    }
}
