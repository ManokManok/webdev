<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/staff')]
class ApiStaffController extends AbstractController
{
    #[Route('/bookings', name: 'api_staff_bookings', methods: ['GET'])]
    public function bookings(BookingRepository $bookingRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied. Staff or admin role required.');
        }

        $bookings = $bookingRepository->findBy([], ['createdAt' => 'DESC']);
        $items = array_map(fn($booking) => [
            'id' => $booking->getId(),
            'user' => [
                'id' => $booking->getUser()?->getId(),
                'email' => $booking->getUser()?->getEmail(),
            ],
            'product' => [
                'id' => $booking->getProduct()?->getId(),
                'name' => $booking->getProduct()?->getName(),
            ],
            'scheduledAt' => $booking->getScheduledAt()?->format('c'),
            'status' => $booking->getStatus(),
            'createdAt' => $booking->getCreatedAt()?->format('c'),
        ], $bookings);

        return $this->json(['status' => 'success', 'data' => $items]);
    }

    #[Route('/orders', name: 'api_staff_orders', methods: ['GET'])]
    public function orders(CustomerOrderRepository $customerOrderRepository): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied. Staff or admin role required.');
        }

        $orders = $customerOrderRepository->findBy([], ['createdAt' => 'DESC']);
        $items = array_map(fn($order) => [
            'id' => $order->getId(),
            'user' => [
                'id' => $order->getUser()?->getId(),
                'email' => $order->getUser()?->getEmail(),
            ],
            'product' => [
                'id' => $order->getProduct()?->getId(),
                'name' => $order->getProduct()?->getName(),
            ],
            'quantity' => $order->getQuantity(),
            'totalAmount' => $order->getTotalAmount(),
            'status' => $order->getStatus(),
            'createdAt' => $order->getCreatedAt()?->format('c'),
        ], $orders);

        return $this->json(['status' => 'success', 'data' => $items]);
    }
}
