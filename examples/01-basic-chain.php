<?php

declare(strict_types=1);

use Nexus\Workflow\Examples\Support\DemoAgent;
use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Prompts\PromptTemplate;

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

