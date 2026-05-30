<?php

namespace App\Message;

/**
 * Deliver a Firebase push notification to a user (handled synchronously by default).
 */
final class SendPushNotification
{
    /**
     * @param array<string, scalar|null> $data
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }
}
