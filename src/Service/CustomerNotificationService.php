<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use App\Realtime\RealtimeTopics;

/**
 * Coordinates Mercure (in-app) and FCM (system push) customer notifications,
 * plus admin dashboard realtime updates.
 */
class CustomerNotificationService
{
    public function __construct(
        private readonly RealtimePublisher $realtime,
        private readonly FcmNotifier $fcm,
    ) {
    }

    public function orderCreated(CustomerOrder $order): void
    {
        $user = $order->getUser();
        if (!$user instanceof User) {
            return;
        }

        $payload = $this->orderPayload($order);

        $this->realtime->publish(
            RealtimeTopics::customerOrders($user->getId()),
            'order.created',
            $payload
        );

        $this->realtime->publish(
            RealtimeTopics::adminOrders(),
            'order.created',
            $this->adminOrderPayload($order)
        );
    }

    public function orderStatusChanged(CustomerOrder $order, string $previousStatus): void
    {
        $user = $order->getUser();
        if (!$user instanceof User) {
            return;
        }

        $payload = [
            ...$this->orderPayload($order),
            'previousStatus' => $previousStatus,
        ];

        $this->realtime->publish(
            RealtimeTopics::customerOrders($user->getId()),
            'order.updated',
            $payload
        );

        $this->realtime->publish(
            RealtimeTopics::adminOrders(),
            'order.updated',
            $this->adminOrderPayload($order, $previousStatus)
        );

        $status = strtoupper((string) $order->getStatus());
        if (in_array($status, ['APPROVED', 'REJECTED', 'PAID', 'CANCELLED'], true)) {
            $title = match ($status) {
                'APPROVED' => 'Order approved',
                'REJECTED' => 'Order rejected',
                'PAID' => 'Payment received',
                'CANCELLED' => 'Order cancelled',
                default => 'Order update',
            };
            $productName = $order->getProduct()?->getName() ?? 'your service';
            $body = match ($status) {
                'APPROVED' => sprintf('Order #%d for %s was approved.', $order->getId(), $productName),
                'REJECTED' => sprintf('Order #%d for %s was rejected.', $order->getId(), $productName),
                'PAID' => sprintf('Order #%d is now paid.', $order->getId()),
                'CANCELLED' => sprintf('Order #%d was cancelled.', $order->getId()),
                default => sprintf('Order #%d status: %s', $order->getId(), $status),
            };

            $this->fcm->sendToUser($user, $title, $body, [
                'type' => 'order.updated',
                'orderId' => (string) $order->getId(),
                'status' => $status,
            ]);
        }

        $booking = $order->getBooking();
        if ($booking instanceof Booking) {
            $this->bookingUpdated($booking);
        }
    }

    public function bookingCreated(Booking $booking): void
    {
        $user = $booking->getUser();
        if (!$user instanceof User) {
            return;
        }

        $payload = $this->bookingPayload($booking);

        $this->realtime->publish(
            RealtimeTopics::customerBookings($user->getId()),
            'booking.created',
            $payload
        );

        $this->realtime->publish(
            RealtimeTopics::adminBookings(),
            'booking.created',
            $this->adminBookingPayload($booking)
        );
    }

    public function bookingUpdated(Booking $booking, ?string $previousStatus = null): void
    {
        $user = $booking->getUser();
        if (!$user instanceof User) {
            return;
        }

        $payload = $this->bookingPayload($booking);
        if ($previousStatus !== null) {
            $payload['previousStatus'] = $previousStatus;
        }

        $this->realtime->publish(
            RealtimeTopics::customerBookings($user->getId()),
            'booking.updated',
            $payload
        );

        $this->realtime->publish(
            RealtimeTopics::adminBookings(),
            'booking.updated',
            $this->adminBookingPayload($booking, $previousStatus)
        );

        $status = strtoupper((string) $booking->getStatus());
        if ($previousStatus !== null && $status === 'CONFIRMED') {
            $productName = $booking->getProduct()?->getName() ?? 'your service';
            $this->fcm->sendToUser($user, 'Booking confirmed', sprintf(
                'Booking #%d for %s is confirmed.',
                $booking->getId(),
                $productName
            ), [
                'type' => 'booking.updated',
                'bookingId' => (string) $booking->getId(),
                'status' => $status,
            ]);
        }
    }

    public function paymentRecorded(Payment $payment): void
    {
        $user = $payment->getUser();
        if (!$user instanceof User) {
            return;
        }

        $orderId = (int) ($payment->getOrder()?->getId() ?? 0);
        $paymentId = (int) $payment->getId();

        $payload = [
            'paymentId' => $paymentId,
            'orderId' => $orderId,
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod(),
            'status' => $payment->getStatus(),
        ];

        $this->realtime->publish(
            RealtimeTopics::customerPayments($user->getId()),
            'payment.created',
            $payload
        );

        $this->realtime->publish(
            RealtimeTopics::adminPayments(),
            'payment.created',
            [
                ...$payload,
                'customerName' => $user->getFullName() ?: $user->getUsername(),
                'customerEmail' => $user->getEmail(),
            ]
        );
    }

    public function productsChanged(string $action, ?Product $product = null): void
    {
        $this->realtime->publish(
            RealtimeTopics::products(),
            'products.'.$action,
            [
                'productId' => $product?->getId(),
                'name' => $product?->getName(),
            ]
        );
    }

    /** @return array<string, mixed> */
    private function orderPayload(CustomerOrder $order): array
    {
        return [
            'orderId' => $order->getId(),
            'status' => $order->getStatus(),
            'totalAmount' => $order->getTotalAmount(),
            'productName' => $order->getProduct()?->getName(),
        ];
    }

    /** @return array<string, mixed> */
    private function bookingPayload(Booking $booking): array
    {
        return [
            'bookingId' => $booking->getId(),
            'status' => $booking->getStatus(),
            'scheduledAt' => $booking->getScheduledAt()?->format(\DateTimeInterface::ATOM),
            'productName' => $booking->getProduct()?->getName(),
            'orderId' => $booking->getOrder()?->getId(),
        ];
    }

    /** @return array<string, mixed> */
    private function adminOrderPayload(CustomerOrder $order, ?string $previousStatus = null): array
    {
        $user = $order->getUser();
        $payload = [
            ...$this->orderPayload($order),
            'customerName' => $user?->getFullName() ?: $user?->getUsername(),
            'customerEmail' => $user?->getEmail(),
        ];
        if ($previousStatus !== null) {
            $payload['previousStatus'] = $previousStatus;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function adminBookingPayload(Booking $booking, ?string $previousStatus = null): array
    {
        $user = $booking->getUser();
        $payload = [
            ...$this->bookingPayload($booking),
            'customerName' => $user?->getFullName() ?: $user?->getUsername(),
            'customerEmail' => $user?->getEmail(),
        ];
        if ($previousStatus !== null) {
            $payload['previousStatus'] = $previousStatus;
        }

        return $payload;
    }
}
