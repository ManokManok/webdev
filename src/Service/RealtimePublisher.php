<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publishes in-app realtime events via Mercure (SSE).
 */
class RealtimePublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly AdminRealtimeEventStore $adminEvents,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function publish(string $topic, string $type, array $payload = []): void
    {
        try {
            $this->hub->publish(new Update(
                $topic,
                json_encode([
                    'type' => $type,
                    'payload' => $payload,
                    'at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ], JSON_THROW_ON_ERROR),
                true
            ));

            if (str_contains($topic, '/admin/')) {
                $this->adminEvents->push($type, $payload);
            }
        } catch (\Throwable $exception) {
            $this->logger?->warning('Mercure publish failed: {message}', [
                'message' => $exception->getMessage(),
                'topic' => $topic,
                'type' => $type,
            ]);
        }
    }
}
