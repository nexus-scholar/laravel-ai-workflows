<?php

declare(strict_types=1);

use NexusScholar\AiChain\Contracts\Retriever;
use NexusScholar\AiChain\Retrieval\Document;
use NexusScholar\AiChain\Retrieval\HybridRetriever;

it('fuses results using RRF', function () {
    $vector = Mockery::mock(Retriever::class);
    $keyword = Mockery::mock(Retriever::class);

    $docA = new Document('A');
    $docB = new Document('B');
    $docC = new Document('C');

    // Vector rank: A (0), B (1)
    $vector->shouldReceive('retrieve')->andReturn([$docA, $docB]);
    // Keyword rank: B (0), C (1)
    $keyword->shouldReceive('retrieve')->andReturn([$docB, $docC]);

    $hybrid = new HybridRetriever($vector, $keyword, k: 60);
    $results = $hybrid->retrieve('query', 3);

    // B should be #1 because it's in both lists
    // Score B = 1/(60+1) + 1/(60+1) = 2/61
    // Score A = 1/(61)
    // Score C = 1/(61)
    expect($results[0]->content)->toBe('B');
    expect($results)->toHaveCount(3);
});
