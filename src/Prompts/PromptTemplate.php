<?php

namespace Nexus\AiChain\Prompts;

use InvalidArgumentException;

final class PromptTemplate
{
    private function __construct(
        private readonly string $template,
        private readonly array $inputVariables,
    ) {}

    public static function from(string $template): self
    {
        preg_match_all('/\{(\w+)\}/', $template, $matches);

        return new self($template, array_unique($matches[1]));
    }

    public function format(array $values): string
    {
        $missing = array_diff($this->inputVariables, array_keys($values));

        if (! empty($missing)) {
            throw new InvalidArgumentException(
                'Missing prompt variables: '.implode(', ', $missing)
            );
        }

        $search = array_map(fn ($v) => '{'.$v.'}', $this->inputVariables);
        $replace = array_map(fn ($v) => (string) $values[$v], $this->inputVariables);

        return str_replace($search, $replace, $this->template);
    }

    public function inputVariables(): array
    {
        return $this->inputVariables;
    }
}
