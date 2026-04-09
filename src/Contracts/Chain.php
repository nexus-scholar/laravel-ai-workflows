<?php

namespace Nexus\Workflow\Contracts;

interface Chain
{
    /**
     * Execute the chain with the given inputs.
     */
    public function run(array $inputs): mixed;

    /**
     * Stream the chain execution.
     *
     * @return \Generator<string>
     */
    public function stream(array $inputs): \Generator;

    /**
     * Get the input key names this chain expects.
     *
     * @return string[]
     */
    public function inputKeys(): array;

    /**
     * Get the output key name this chain produces.
     */
    public function outputKey(): string;
}
