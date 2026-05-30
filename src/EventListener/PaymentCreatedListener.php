<?php

namespace App\EventListener;

use App\Entity\Payment;
use App\Service\DeferredCustomerNotifications;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Payment::class)]
final class PaymentCreatedListener
{
    public function __construct(
        private readonly DeferredCustomerNotifications $deferred,
    ) {
    }

    public function postPersist(Payment $payment, PostPersistEventArgs $args): void
    {
        $this->deferred->queuePaymentRecorded($payment);
    }
}
