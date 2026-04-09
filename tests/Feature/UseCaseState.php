<?php

declare(strict_types=1);

namespace Nexus\Workflow\Tests\Feature;

use Nexus\Workflow\Graph\State;

final class UseCaseState extends State
{
    public function __construct(public int $count = 0, public array $events = []) {}

    public function toArray(): array
    {
        return ['count' => $this->count, 'events' => $this->events];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            count: $data['count'] ?? 0,
            events: $data['events'] ?? [],
        );
    }
}

