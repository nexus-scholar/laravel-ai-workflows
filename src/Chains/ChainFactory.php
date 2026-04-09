<?php

namespace Nexus\AiChain\Chains;

use InvalidArgumentException;
use Laravel\Ai\Contracts\Agent;
use Nexus\AiChain\Contracts\Chain as ChainContract;
use Nexus\AiChain\Prompts\PromptTemplate;

final class ChainFactory
{
    /** @var ChainContract[] */
    private array $chains = [];

    private function __construct() {}

    public static function chain(Agent $agent, PromptTemplate $promptTemplate, string $outputKey = 'output'): self
    {
        $factory = new self;
        $factory->chains[] = Chain::make($agent, $promptTemplate, $outputKey);

        return $factory;
    }

    public static function from(ChainContract $chain): self
    {
        $factory = new self;
        $factory->chains[] = $chain;

        return $factory;
    }

    public function then(ChainContract $chain): self
    {
        $this->chains[] = $chain;

        return $this;
    }

    public function thenPrompt(Agent $agent, PromptTemplate $promptTemplate, string $outputKey = 'output'): self
    {
        return $this->then(Chain::make($agent, $promptTemplate, $outputKey));
    }

    public function build(): ChainContract
    {
        $count = count($this->chains);

        if ($count === 0) {
            throw new InvalidArgumentException('ChainFactory requires at least one chain before build().');
        }

        if ($count === 1) {
            return $this->chains[0];
        }

        return new SequentialChain($this->chains);
    }

    public function buildSequential(): SequentialChain
    {
        if ($this->chains === []) {
            throw new InvalidArgumentException('ChainFactory requires at least one chain before buildSequential().');
        }

        return new SequentialChain($this->chains);
    }
}
