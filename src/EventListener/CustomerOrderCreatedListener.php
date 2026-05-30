<?php

namespace App\EventListener;

use App\Entity\CustomerOrder;
use App\Service\DeferredCustomerNotifications;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: CustomerOrder::class)]
final class CustomerOrderCreatedListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
    ) {
    }

    public function postPersist(CustomerOrder $order, PostPersistEventArgs $args): void
    {
        $this->deferred->queueOrderCreated($order);
    }
}
