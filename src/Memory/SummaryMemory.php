<?php

namespace Nexus\AiChain\Memory;

use InvalidArgumentException;
use Nexus\AiChain\Contracts\Memory;

/**
 * Compresses old messages into a rolling summary using an LLM.
 * Ideal for long research sessions where token budget is tight.
 */
final class SummaryMemory implements Memory
{
    private string $summary = '';

    private array $messages = [];

    /**
     * @param  callable  $summarizer  A callable that receives (string $history, string $previousSummary) and returns string.
     */
    public function __construct(
        private readonly mixed $summarizer,
        private readonly int $summarizeAfter = 10,
    ) {
        /** @phpstan-ignore function.alreadyNarrowedType */
        if (! is_callable($this->summarizer)) {
            throw new InvalidArgumentException('summarizer must be callable.');
        }

        if ($this->summarizeAfter < 2) {
            throw new InvalidArgumentException('summarizeAfter must be at least 2.');
        }
    }

    public function add(string $role, string $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];

        if (count($this->messages) >= $this->summarizeAfter) {
            $this->compress();
        }
    }

    public function messages(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->summary = '';
        $this->messages = [];
    }

    public function asString(): string
    {
        $parts = [];

        if ($this->summary !== '') {
            $parts[] = "SUMMARY OF EARLIER CONVERSATION:\n{$this->summary}";
        }

        if (! empty($this->messages)) {
            $parts[] = $this->messagesAsString($this->messages);
        }

        return implode("\n\n", array_filter($parts));
    }

    private function compress(): void
    {
        $chunkSize = max(1, intdiv($this->summarizeAfter, 2));
        $toCompress = array_slice($this->messages, 0, $chunkSize);
        $remaining = array_slice($this->messages, $chunkSize);

        $conversation = $this->messagesAsString($toCompress);

        try {
            $nextSummary = ($this->summarizer)($conversation, $this->summary);
        } catch (\Throwable) {
            return;
        }

        $nextSummary = trim((string) $nextSummary);

        if ($nextSummary === '') {
            return;
        }

        $this->summary = $nextSummary;
        $this->messages = $remaining;
    }

    private function messagesAsString(array $messages): string
    {
        return implode("\n", array_map(
            fn ($m) => strtoupper($m['role']).': '.$m['content'],
            $messages
        ));
    }
}
