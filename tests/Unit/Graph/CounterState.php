<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Unit\Graph;

use Nexus\Workflow\Graph\State;

final class CounterState extends State
{
    public function __construct(public int $count = 0) {}

    public function toArray(): array
    {
        return ['count' => $this->count];
    }

    public static function fromArray(array $data): static
    {
        return new self($data['count']);
    }
}

