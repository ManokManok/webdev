<?php

namespace App\EventListener;

use App\Service\CustomerNotificationService;
use App\Service\DeferredCustomerNotifications;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::postFlush)]
final class FlushCustomerNotificationsListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
        private readonly CustomerNotificationService $notifications,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // HTTP requests flush on kernel terminate (after response). CLI flushes immediately.
        if ($this->requestStack->getMainRequest() !== null) {
            return;
        }

        $this->deferred->flush($this->notifications);
    }
}
