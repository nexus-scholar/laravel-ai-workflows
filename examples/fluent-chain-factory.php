<?php

declare(strict_types=1);

use Nexus\Workflow\Chains\Chain;
use Nexus\Workflow\Examples\Support\DemoAgent;
use Nexus\Workflow\Prompts\PromptTemplate;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Support/DemoAgent.php';

$agent = new DemoAgent([
    'Draft response from first stage.',
    'Refined bullet-point answer from second stage.',
]);

$root = Chain::make(
    $agent,
    PromptTemplate::from('Question: {input}'),
    outputKey: 'draft'
);

$refine = Chain::make(
    $agent,
    PromptTemplate::from('Draft: {draft}'),
    outputKey: 'final'
);

$workflow = $root->then($refine);

$result = $workflow->run(['input' => 'How can teams adopt AI safely?']);

echo "Final output:\n";
echo (string) $result.PHP_EOL;

