<?php

namespace Nexus\Workflow\Chains;

use InvalidArgumentException;
use Nexus\Workflow\Contracts\Chain as ChainContract;

/**
 * Pipes multiple chains together.
 * The output of chain N becomes part of the inputs for chain N+1.
 */
final class SequentialChain implements ChainContract
{
    /** @param ChainContract[] $chains */
    public function __construct(private readonly array $chains)
    {
        if ($this->chains === []) {
            throw new InvalidArgumentException('SequentialChain requires at least one chain.');
        }

        foreach ($this->chains as $index => $chain) {
            /** @phpstan-ignore instanceof.alwaysTrue */
            if (! $chain instanceof ChainContract) {
                throw new InvalidArgumentException("SequentialChain item at index {$index} must implement ".ChainContract::class.'.');
            }
        }
    }

    public static function compose(ChainContract $chain): ChainFactory
    {
        return ChainFactory::from($chain);
    }

    /**
     * @param  ChainContract[]  $chains
     */
    public static function from(array $chains): self
    {
        return new self($chains);
    }

    public function run(array $inputs): mixed
    {
        $state = $inputs;

        foreach ($this->chains as $chain) {
            $result = $chain->run($state);
            $state[$chain->outputKey()] = $result;
        }

        return $state[$this->lastChain()->outputKey()];
    }

    public function stream(array $inputs): \Generator
    {
        // Stream only the final chain
        $state = $inputs;
        $chains = $this->chains;
        $last = array_pop($chains);

        foreach ($chains as $chain) {
            $state[$chain->outputKey()] = $chain->run($state);
        }

        yield from $last->stream($state);
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return iterable<int|string, mixed>
     */
    public function streamEvents(array $inputs): iterable
    {
        $state = $inputs;
        $chains = $this->chains;
        $last = array_pop($chains);

        foreach ($chains as $chain) {
            $state[$chain->outputKey()] = $chain->run($state);
        }

        return $last->streamEvents($state);
    }

    public function inputKeys(): array
    {
        return $this->firstChain()->inputKeys();
    }

    public function outputKey(): string
    {
        return $this->lastChain()->outputKey();
    }

    private function firstChain(): ChainContract
    {
        return $this->chains[0];
    }

    private function lastChain(): ChainContract
    {
        return $this->chains[array_key_last($this->chains)];
    }
}
