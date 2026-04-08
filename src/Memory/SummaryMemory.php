<?php

namespace Nexus\AiChain\Memory;

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
    ) {}

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
        $toCompress = array_splice($this->messages, 0, $this->summarizeAfter / 2); // Keep half
        $conversation = $this->messagesAsString($toCompress);

        $this->summary = ($this->summarizer)($conversation, $this->summary);
    }

    private function messagesAsString(array $messages): string
    {
        return implode("\n", array_map(
            fn ($m) => strtoupper($m['role']).': '.$m['content'],
            $messages
        ));
    }
}
