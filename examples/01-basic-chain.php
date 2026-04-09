<?php

declare(strict_types=1);

use Nexus\AiChain\Examples\Support\DemoAgent;
use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Prompts\PromptTemplate;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Support/DemoAgent.php';

$agent = new DemoAgent(['RAG combines retrieval with generation.']);

$chain = Chain::make(
    $agent,
    PromptTemplate::from('Question: {input}')
);

$result = $chain->run(['input' => 'What is RAG?']);

echo "Answer:\n";
echo (string) $result.PHP_EOL;

