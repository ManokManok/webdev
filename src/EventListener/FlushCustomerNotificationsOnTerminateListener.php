<?php

namespace App\EventListener;

use App\Service\CustomerNotificationService;
use App\Service\DeferredCustomerNotifications;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Publishes Mercure/FCM after the HTTP response is sent so API clients are not blocked.
 */
#[AsEventListener(event: KernelEvents::TERMINATE, method: 'onTerminate')]
final class FlushCustomerNotificationsOnTerminateListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
        private readonly CustomerNotificationService $notifications,
    ) {
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->deferred->flush($this->notifications);
    }
}
