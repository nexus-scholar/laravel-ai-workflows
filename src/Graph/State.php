<?php

namespace Nexus\Workflow\Graph;

/**
 * Base class for all graph states.
 * Must be immutable.
 */
abstract class State
{
    /**
     * Convert state to an associative array.
     */
    abstract public function toArray(): array;

    /**
     * Create a new state instance from an array.
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Return a new instance with the given updates.
     */
    public function with(array $updates): static
    {
        return static::fromArray(array_merge($this->toArray(), $updates));
    }
}
