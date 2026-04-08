<?php

namespace Nexus\AiChain\Retrieval;

final readonly class Document
{
    public function __construct(
        public string $content,
        public array $metadata = [],
        public ?float $score = null,
    ) {}

    public function withScore(float $score): self
    {
        return new self($this->content, $this->metadata, $score);
    }
}
