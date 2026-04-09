<?php

namespace Nexus\AiChain;

use Laravel\Ai\Contracts\Agent;
use Nexus\AiChain\Chains\ChainFactory;
use Nexus\AiChain\Contracts\Chain as ChainContract;
use Nexus\AiChain\Prompts\PromptTemplate;

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
