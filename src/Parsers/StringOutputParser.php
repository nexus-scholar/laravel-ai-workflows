<?php

namespace NexusScholar\AiChain\Parsers;

use NexusScholar\AiChain\Contracts\OutputParser;

final class StringOutputParser implements OutputParser
{
    public function parse(string $text): string
    {
        return $text;
    }

    public function formatInstructions(): string
    {
        return '';
    }
}
