<?php

namespace NexusScholar\AiChain\Chains;

use NexusScholar\AiChain\Contracts\Chain as ChainContract;

/**
 * Pipes multiple chains together.
 * The output of chain N becomes part of the inputs for chain N+1.
 */
final class SequentialChain implements ChainContract
{
    /** @param ChainContract[] $chains */
    public function __construct(private readonly array $chains) {}

    public function run(array $inputs): mixed
    {
        $state = $inputs;

        foreach ($this->chains as $chain) {
            $result = $chain->run($state);
            $state[$chain->outputKey()] = $result;
        }

        return $state[last($this->chains)->outputKey()];
    }

    public function stream(array $inputs): \Generator
    {
        // Stream only the final chain
        $state = $inputs;
        $chains = $this->chains;
        $last   = array_pop($chains);

        foreach ($chains as $chain) {
            $state[$chain->outputKey()] = $chain->run($state);
        }

        yield from $last->stream($state);
    }

    public function inputKeys(): array
    {
        return $this->chains[0]->inputKeys();
    }

    public function outputKey(): string
    {
        return last($this->chains)->outputKey();
    }
}
