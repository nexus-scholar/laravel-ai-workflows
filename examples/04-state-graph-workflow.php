<?php

declare(strict_types=1);

use Nexus\AiChain\Graph\State;
use Nexus\AiChain\Graph\StateGraph;

require __DIR__.'/../vendor/autoload.php';

final class PipelineState extends State
{
    public function __construct(
        public int $step = 0,
        public array $notes = [],
    ) {}

    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'notes' => $this->notes,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            step: $data['step'] ?? 0,
            notes: $data['notes'] ?? [],
        );
    }
}

$graph = new StateGraph;

$graph->addNode('collect', fn (PipelineState $s) => $s->with([
    'step' => $s->step + 1,
    'notes' => array_merge($s->notes, ['collect sources']),
]));

$graph->addNode('draft', fn (PipelineState $s) => $s->with([
    'step' => $s->step + 1,
    'notes' => array_merge($s->notes, ['draft answer']),
]));

$graph->setEntryPoint('collect');
$graph->addEdge('collect', 'draft');
$graph->addEdge('draft', StateGraph::END);

$final = $graph->compile()->invoke(new PipelineState);

echo "Steps: {$final->step}\n";
echo "Notes: ".implode(', ', $final->notes).PHP_EOL;

