<?php

namespace NexusScholar\AiChain\Prompts;

final class ChatPromptTemplate
{
    /** @param array<array{role: string, template: string}> $messages */
    private function __construct(private readonly array $messages) {}

    public static function fromMessages(array $messages): self
    {
        return new self($messages);
    }

    /**
     * Format all message templates with the given values.
     * Returns array ready for injection into a laravel/ai Agent's instructions().
     *
     * @return array<array{role: string, content: string}>
     */
    public function format(array $values): array
    {
        return array_map(function (array $message) use ($values) {
            $tpl = PromptTemplate::from($message['template']);
            return ['role' => $message['role'], 'content' => $tpl->format($values)];
        }, $this->messages);
    }
}
