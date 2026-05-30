<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\CustomerOrder;
use App\Entity\Payment;

/**
 * Queues customer/admin realtime notifications until after Doctrine flush (committed data).
 */
final class DeferredCustomerNotifications
{
    /** @var CustomerOrder[] */
    private array $ordersCreated = [];

    /** @var array{0: CustomerOrder, 1: string}[] */
    private array $ordersStatusChanged = [];

    /** @var Booking[] */
    private array $bookingsCreated = [];

    /** @var array{0: Booking, 1: ?string}[] */
    private array $bookingsUpdated = [];

    /** @var Payment[] */
    private array $paymentsRecorded = [];

    public function queueOrderCreated(CustomerOrder $order): void
    {
        $this->ordersCreated[$order->getId() ?? spl_object_id($order)] = $order;
    }

    public function queueOrderStatusChanged(CustomerOrder $order, string $previousStatus): void
    {
        $this->ordersStatusChanged[$order->getId()] = [$order, $previousStatus];
    }

    public function queueBookingCreated(Booking $booking): void
    {
        $this->bookingsCreated[$booking->getId() ?? spl_object_id($booking)] = $booking;
    }

    public function queueBookingUpdated(Booking $booking, ?string $previousStatus = null): void
    {
        $this->bookingsUpdated[$booking->getId()] = [$booking, $previousStatus];
    }

    public function queuePaymentRecorded(Payment $payment): void
    {
        $this->paymentsRecorded[$payment->getId() ?? spl_object_id($payment)] = $payment;
    }

    public function flush(CustomerNotificationService $notifications): void
    {
        foreach ($this->ordersCreated as $order) {
            $notifications->orderCreated($order);
        }
        $this->ordersCreated = [];

        foreach ($this->ordersStatusChanged as [$order, $previous]) {
            $notifications->orderStatusChanged($order, $previous);
        }
        $this->ordersStatusChanged = [];

        foreach ($this->bookingsCreated as $booking) {
            $notifications->bookingCreated($booking);
        }
        $this->bookingsCreated = [];

        foreach ($this->bookingsUpdated as [$booking, $previous]) {
            $notifications->bookingUpdated($booking, $previous);
        }
        $this->bookingsUpdated = [];

        foreach ($this->paymentsRecorded as $payment) {
            $notifications->paymentRecorded($payment);
        }
        $this->paymentsRecorded = [];
    }
}
