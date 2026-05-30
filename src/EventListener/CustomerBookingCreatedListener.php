<?php

namespace App\EventListener;

use App\Entity\Booking;
use App\Service\DeferredCustomerNotifications;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Booking::class)]
final class CustomerBookingCreatedListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
    ) {
    }

    public function postPersist(Booking $booking, PostPersistEventArgs $args): void
    {
        $this->deferred->queueBookingCreated($booking);
    }
}
