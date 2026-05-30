<?php

namespace App\EventListener;

use App\Entity\Booking;
use App\Service\DeferredCustomerNotifications;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Booking::class)]
final class CustomerBookingStatusListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
    ) {
    }

    public function postUpdate(Booking $booking, PostUpdateEventArgs $args): void
    {
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($booking);
        if (!isset($changeSet['status'])) {
            return;
        }

        [$previous, $new] = $changeSet['status'];
        if ($previous === $new) {
            return;
        }

        $this->deferred->queueBookingUpdated($booking, (string) $previous);
    }
}
