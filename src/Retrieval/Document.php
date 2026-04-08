<?php

namespace NexusScholar\AiChain\Retrieval;

final readonly class Document
{
    public function __construct(
        public string $content,
        public array  $metadata = [],
        public ?float $score    = null,
    ) {}
}
