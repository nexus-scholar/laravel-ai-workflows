<?php

namespace Nexus\Workflow;

use Laravel\Ai\Contracts\Agent;
use Nexus\Workflow\Chains\ChainFactory;
use Nexus\Workflow\Contracts\Chain as ChainContract;
use Nexus\Workflow\Prompts\PromptTemplate;

final class AiChainManager
{
    public function chain(Agent $agent, PromptTemplate $promptTemplate, string $outputKey = 'output'): ChainFactory
    {
        return ChainFactory::chain($agent, $promptTemplate, $outputKey);
    }

    public function from(ChainContract $chain): ChainFactory
    {
        return ChainFactory::from($chain);
    }
}
