<?php

namespace Nexus\Workflow\Tests;

use Illuminate\Bus\BusServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Laravel\Ai\AiServiceProvider;
use Nexus\Workflow\AiChainServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BusServiceProvider::class,
            QueueServiceProvider::class,
            AiServiceProvider::class,
            AiChainServiceProvider::class,
        ];
    }
}
