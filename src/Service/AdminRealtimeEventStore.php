<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Recent admin realtime events for polling fallback (when SSE is unavailable).
 */
final class AdminRealtimeEventStore
{
    private const CACHE_KEY = 'admin_realtime_events_v1';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function push(string $type, array $payload): void
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        /** @var list<array{id: int, type: string, payload: array<string, mixed>, at: string}> $events */
        $events = $item->isHit() ? $item->get() : [];

        $events[] = [
            'id' => (int) floor(microtime(true) * 1000),
            'type' => $type,
            'payload' => $payload,
            'at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        if (count($events) > 100) {
            $events = array_slice($events, -100);
        }

        $item->set($events);
        $this->cache->save($item);
    }

    /**
     * @return list<array{id: int, type: string, payload: array<string, mixed>, at: string}>
     */
    public function since(int $sinceId): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        /** @var list<array{id: int, type: string, payload: array<string, mixed>, at: string}> $events */
        $events = $item->isHit() ? $item->get() : [];

        return array_values(array_filter($events, static fn (array $event): bool => $event['id'] > $sinceId));
    }
}
