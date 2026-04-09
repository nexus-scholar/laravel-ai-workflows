<?php

declare(strict_types=1);

use Nexus\Workflow\AiChainManager;
use Nexus\Workflow\Examples\Support\DemoAgent;
use Nexus\Workflow\Prompts\PromptTemplate;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Support/DemoAgent.php';

$agent = new DemoAgent([
    'Draft answer from stage 1.',
    'Final answer from stage 2.',
]);

$manager = new AiChainManager;

$pipeline = $manager
    ->chain($agent, PromptTemplate::from('Question: {input}'), 'draft')
    ->thenPrompt($agent, PromptTemplate::from('Draft: {draft}'), 'final')
    ->build();

$result = $pipeline->run(['input' => 'Give a two-stage explanation of embeddings']);

echo "Final:\n";
echo (string) $result.PHP_EOL;

