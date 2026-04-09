<?php

declare(strict_types=1);

use Nexus\AiChain\Examples\Support\DemoAgent;
use Nexus\AiChain\Chains\Chain;
use Nexus\AiChain\Memory\InMemoryConversation;
use Nexus\AiChain\Prompts\PromptTemplate;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Support/DemoAgent.php';

$agent = new DemoAgent([
    'Initial summary response.',
    'Follow-up response using conversation history.',
]);

$memory = new InMemoryConversation;

$chain = Chain::make(
    $agent,
    PromptTemplate::from("History:\n{history}\n\nQuestion: {input}")
)->withMemory($memory);

$first = $chain->run(['input' => 'Summarize zero-shot prompting']);
$second = $chain->run(['input' => 'Now refine it as three bullets']);

echo "First:\n{$first}\n\n";
echo "Second:\n{$second}\n\n";
echo "Captured Memory:\n";
echo $memory->asString().PHP_EOL;

