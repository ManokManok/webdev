<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use App\Repository\CustomerOrderRepository;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminBookingController extends AbstractController
{
    #[Route('/bookings', name: 'bookings', methods: ['GET'])]
    public function bookings(BookingRepository $bookingRepository): Response
    {
        $bookings = $bookingRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/bookings/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/orders', name: 'orders', methods: ['GET'])]
    public function orders(CustomerOrderRepository $customerOrderRepository): Response
    {
        $orders = $customerOrderRepository->findStandaloneBy(['createdAt' => 'DESC']);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/payments', name: 'payments', methods: ['GET'])]
    public function payments(PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/payments/index.html.twig', [
            'payments' => $payments,
        ]);
    }
}
