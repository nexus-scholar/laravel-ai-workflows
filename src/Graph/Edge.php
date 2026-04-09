<?php

namespace Nexus\Workflow\Graph;

use Closure;

final readonly class Edge
{
    public function __construct(
        public string $from,
        public ?string $to = null,
        public ?Closure $condition = null,
    ) {}

    public static function direct(string $from, string $to): self
    {
        return new self($from, $to);
    }

    public static function conditional(string $from, Closure $condition): self
    {
        return new self($from, condition: $condition);
    }

    public function isConditional(): bool
    {
        return $this->condition !== null;
    }
}
