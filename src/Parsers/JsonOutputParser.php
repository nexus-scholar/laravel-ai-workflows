<?php

namespace NexusScholar\AiChain\Parsers;

use NexusScholar\AiChain\Contracts\OutputParser;
use RuntimeException;

final class JsonOutputParser implements OutputParser
{
    public function parse(string $text): array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned);

        $decoded = json_decode(trim($cleaned), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Failed to parse JSON output: ' . json_last_error_msg() . "\nRaw: {$text}"
            );
        }

        return $decoded;
    }

    public function formatInstructions(): string
    {
        return 'Respond with valid JSON only. Do not include markdown code fences.';
    }
}
