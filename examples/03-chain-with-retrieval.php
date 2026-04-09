<?php

declare(strict_types=1);

use Nexus\AiChain\Examples\Support\DemoAgent;
use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Prompts\PromptTemplate;
use Nexus\AiChain\Retrieval\VectorStoreRetriever;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Support/DemoAgent.php';

$agent = new DemoAgent(['Retrieved context incorporated into answer.']);

$retriever = new VectorStoreRetriever(
    fn (string $query, int $topK): array => [
        ['content' => 'RAG = retrieve relevant docs before generation.', 'score' => 0.98],
        ['content' => 'Chunking and reranking improve answer quality.', 'score' => 0.91],
    ]
);

$chain = Chain::make(
    $agent,
    PromptTemplate::from("Context:\n{context}\n\nQuestion: {input}")
)->withRetriever($retriever, topK: 2);

$result = $chain->run(['input' => 'How do I improve grounded QA?']);

echo "Answer:\n";
echo (string) $result.PHP_EOL;

