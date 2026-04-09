<?php

namespace Nexus\Workflow\Memory;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Nexus\Workflow\Contracts\Memory;

final class CacheConversationMemory implements Memory
{
    private array $messages = [];

    public function __construct(
        private readonly string $sessionId,
        private readonly int $maxMessages = 20,
        private readonly string $store = 'file', // Default to file for broader compatibility
        private readonly int $ttl = 3600,
    ) {
        if ($this->maxMessages <= 0) {
            throw new InvalidArgumentException('maxMessages must be greater than 0.');
        }

        $cached = Cache::store($this->store)
            ->get("ai_memory:{$this->sessionId}", []);

        $this->messages = $this->normalizeMessages($cached);

        if (count($this->messages) > $this->maxMessages) {
            $this->messages = array_slice($this->messages, -$this->maxMessages);
        }
    }

    public function add(string $role, string $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];

        // Sliding window — keep only the last N messages
        if (count($this->messages) > $this->maxMessages) {
            $this->messages = array_slice($this->messages, -$this->maxMessages);
        }

        $store = Cache::store($this->store);

        if ($this->ttl <= 0) {
            $store->forever("ai_memory:{$this->sessionId}", $this->messages);

            return;
        }

        $store->put("ai_memory:{$this->sessionId}", $this->messages, $this->ttl);
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
            fn ($m) => strtoupper($m['role']).': '.$m['content'],
            $this->messages
        ));
    }

    private function normalizeMessages(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $item): ?array {
            if (! is_array($item) || ! isset($item['role'], $item['content'])) {
                return null;
            }

            return [
                'role' => (string) $item['role'],
                'content' => (string) $item['content'],
            ];
        }, $raw)));
    }
}
