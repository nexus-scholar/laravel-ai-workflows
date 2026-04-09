<?php

namespace Nexus\AiChain\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Ai\Contracts\Agent;
use Nexus\AiChain\AiChainManager;
use Nexus\AiChain\Chains\ChainFactory;
use Nexus\AiChain\Prompts\PromptTemplate;

/**
 * @method static ChainFactory chain(Agent $agent, PromptTemplate $promptTemplate, string $outputKey = 'output')
 * @method static ChainFactory from(Agent $agent, PromptTemplate $promptTemplate, string $outputKey = 'output')
 *
 * @see AiChainManager
 */
final class AiChain extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiChainManager::class;
    }
}
