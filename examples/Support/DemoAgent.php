<?php

declare(strict_types=1);

namespace Nexus\Workflow\Examples\Support;

use Illuminate\Broadcasting\Channel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\FakePendingDispatch;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Stringable;

final class DemoAgent implements Agent
{
    /** @var list<string> */
    private array $responses;

    private int $cursor = 0;

    /**
     * @param list<string> $responses
     */
    public function __construct(array $responses, private readonly string $instructions = 'Demo agent')
    {
        $this->responses = $responses;
    }

    public function instructions(): Stringable|string
    {
        return $this->instructions;
    }

    public function prompt(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): AgentResponse {
        $text = $this->nextResponse();

        return new AgentResponse(
            invocationId: uniqid('demo_', true),
            text: $text,
            usage: new Usage,
            meta: new Meta(is_string($provider) ? $provider : 'demo', $model ?? 'demo-model'),
        );
    }

    public function stream(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        $text = $this->nextResponse();

        return new StreamableAgentResponse(
            invocationId: uniqid('demo_stream_', true),
            generator: function () use ($text) {
                $messageId = uniqid('msg_', true);
                foreach (preg_split('/\s+/', trim($text)) as $index => $word) {
                    if ($word === '') {
                        continue;
                    }

                    yield new TextDelta(
                        id: uniqid('delta_', true),
                        messageId: $messageId,
                        delta: $index === 0 ? $word : ' '.$word,
                        timestamp: time(),
                    );
                }

                yield new StreamEnd(
                    id: uniqid('end_', true),
                    reason: 'stop',
                    usage: new Usage,
                    timestamp: time(),
                );
            },
            meta: new Meta(is_string($provider) ? $provider : 'demo', $model ?? 'demo-model'),
        );
    }

    public function queue(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        return new QueuedAgentResponse(new FakePendingDispatch);
    }

    public function broadcast(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        bool $now = false,
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->stream($prompt, $attachments, $provider, $model);
    }

    public function broadcastNow(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): StreamableAgentResponse {
        return $this->stream($prompt, $attachments, $provider, $model);
    }

    public function broadcastOnQueue(
        string $prompt,
        Channel|array $channels,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null
    ): QueuedAgentResponse {
        return $this->queue($prompt, $attachments, $provider, $model);
    }

    private function nextResponse(): string
    {
        if ($this->responses === []) {
            return 'Demo response.';
        }

        $response = $this->responses[$this->cursor] ?? end($this->responses);
        $this->cursor++;

        return (string) $response;
    }
}

