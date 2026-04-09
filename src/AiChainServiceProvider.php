<?php

namespace Nexus\Workflow;

use Illuminate\Support\ServiceProvider;
use Nexus\Workflow\Contracts\Checkpointable;
use Nexus\Workflow\Graph\Checkpoint\CacheCheckpoint;

class AiChainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-chain.php',
            'ai-chain'
        );

        $this->app->singleton(CacheCheckpoint::class, function (): CacheCheckpoint {
            return new CacheCheckpoint(
                store: (string) config('ai-chain.graph.checkpoint.store', 'file'),
                ttl: (int) config('ai-chain.graph.checkpoint.ttl', 86400),
            );
        });

        $this->app->singleton(AiChainManager::class, fn (): AiChainManager => new AiChainManager);

        $this->app->bind(Checkpointable::class, CacheCheckpoint::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ai-chain.php' => config_path('ai-chain.php'),
        ], 'ai-chain-config');
    }
}
