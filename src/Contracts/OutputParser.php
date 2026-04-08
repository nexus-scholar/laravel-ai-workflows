<?php

namespace NexusScholar\AiChain\Contracts;

interface OutputParser
{
    /**
     * Parse the LLM output string into a structured format.
     */
    public function parse(string $text): mixed;

    /**
     * Instructions to be injected into the prompt to guide the LLM format.
     */
    public function formatInstructions(): string;
}
