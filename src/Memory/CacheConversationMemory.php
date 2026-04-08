<?php

namespace NexusScholar\AiChain\Memory;

use Illuminate\Support\Facades\Cache;
use NexusScholar\AiChain\Contracts\Memory;

final class CacheConversationMemory implements Memory
{
    private array $messages = [];

    public function __construct(
        private readonly string $sessionId,
        private readonly int    $maxMessages = 20,
        private readonly string $store       = 'file', // Default to file for broader compatibility
        private readonly int    $ttl         = 3600,
    ) {
        $this->messages = Cache::store($this->store)
            ->get("ai_memory:{$this->sessionId}", []);
    }

    public function add(string $role, string $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];

        // Sliding window — keep only the last N messages
        if (count($this->messages) > $this->maxMessages) {
            $this->messages = array_slice($this->messages, -$this->maxMessages);
        }

        Cache::store($this->store)
            ->put("ai_memory:{$this->sessionId}", $this->messages, $this->ttl);
    }

    public function messages(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
        Cache::store($this->store)->forget("ai_memory:{$this->sessionId}");
    }

    public function asString(): string
    {
        return implode("\n", array_map(
            fn ($m) => strtoupper($m['role']) . ': ' . $m['content'],
            $this->messages
        ));
    }
}
