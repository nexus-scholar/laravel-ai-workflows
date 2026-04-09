<?php

namespace Nexus\Workflow\Facades;

use Illuminate\Support\Facades\Facade;
use Laravel\Ai\Contracts\Agent;
use Nexus\Workflow\AiChainManager;
use Nexus\Workflow\Chains\ChainFactory;
use Nexus\Workflow\Prompts\PromptTemplate;

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
