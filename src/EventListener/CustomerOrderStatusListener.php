<?php

namespace App\EventListener;

use App\Entity\CustomerOrder;
use App\Service\DeferredCustomerNotifications;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Ensures approve/reject (and any status change) always triggers Mercure + push,
 * even when status is updated outside admin/API controllers.
 */
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: CustomerOrder::class)]
final class CustomerOrderStatusListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
    ) {
    }

    public function postUpdate(CustomerOrder $order, PostUpdateEventArgs $args): void
    {
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($order);
        if (!isset($changeSet['status'])) {
            return;
        }

        [$previous, $new] = $changeSet['status'];
        if ($previous === $new) {
            return;
        }

        $this->deferred->queueOrderStatusChanged($order, (string) $previous);
    }
}
