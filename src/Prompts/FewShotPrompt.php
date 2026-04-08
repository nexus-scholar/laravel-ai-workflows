<?php

namespace NexusScholar\AiChain\Prompts;

final class FewShotPrompt
{
    public function __construct(
        private readonly string $prefix,
        private readonly array  $examples,
        private readonly string $suffix,
        private readonly string $separator = "\n\n",
    ) {}

    public function format(array $values): string
    {
        $prefix = PromptTemplate::from($this->prefix)->format($values);
        $suffix = PromptTemplate::from($this->suffix)->format($values);

        $parts = [$prefix];
        foreach ($this->examples as $example) {
            $parts[] = $example; // Could also support PromptTemplate for examples if needed
        }
        $parts[] = $suffix;

        return implode($this->separator, array_filter($parts, fn($p) => trim($p) !== ''));
    }
}
