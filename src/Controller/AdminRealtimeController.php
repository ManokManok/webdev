<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Realtime\RealtimeTopics;
use App\Service\AdminRealtimeEventStore;
use App\Repository\BookingRepository;
use App\Repository\CustomerOrderRepository;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Lcobucci\JWT\Signer\InvalidKeyProvided;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin/realtime')]
#[IsGranted('ROLE_ADMIN')]
class AdminRealtimeController extends AbstractController
{
    #[Route('/token', name: 'app_admin_realtime_token', methods: ['GET'])]
    public function token(
        #[Autowire(service: 'mercure.hub.default.jwt.factory')]
        TokenFactoryInterface $tokenFactory,
        #[Autowire('%env(default::MERCURE_PUBLIC_URL)%')]
        string $mercurePublicUrl,
    ): JsonResponse {
        if ($mercurePublicUrl === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'Realtime is not configured on the server (MERCURE_PUBLIC_URL).',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $topics = RealtimeTopics::forAdmin();
        try {
            $subscriberToken = $tokenFactory->create($topics, []);
        } catch (InvalidKeyProvided $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Mercure JWT secret is invalid (must be at least 32 characters). Set MERCURE_JWT_SECRET on the server.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'status' => 'success',
            'data' => [
                'hubUrl' => rtrim($mercurePublicUrl, '/'),
                'streamUrl' => $this->generateUrl('app_admin_realtime_events'),
                'token' => $subscriberToken,
                'topics' => $topics,
            ],
        ]);
    }

    #[Route('/poll', name: 'poll', methods: ['GET'])]
    public function poll(Request $request, AdminRealtimeEventStore $events): JsonResponse
    {
        $since = max(0, (int) $request->query->get('since', 0));

        return $this->json([
            'status' => 'success',
            'data' => $events->since($since),
        ]);
    }

    /**
     * Same-origin SSE proxy — avoids browser cross-origin issues (localhost vs 127.0.0.1).
     */
    #[Route('/events', name: 'events', methods: ['GET'])]
    public function events(
        HttpClientInterface $httpClient,
        #[Autowire(service: 'mercure.hub.default.jwt.factory')]
        TokenFactoryInterface $tokenFactory,
        #[Autowire('%env(default::MERCURE_URL)%')]
        string $mercureInternalUrl,
    ): StreamedResponse {
        if ($mercureInternalUrl === '') {
            return new StreamedResponse(
                static function (): void {
                    echo "event: error\ndata: {\"message\":\"Mercure is not configured (MERCURE_URL).\"}\n\n";
                    flush();
                },
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/event-stream; charset=UTF-8']
            );
        }

        $topics = RealtimeTopics::forAdmin();
        try {
            $token = $tokenFactory->create($topics, []);
        } catch (InvalidKeyProvided) {
            return new StreamedResponse(
                static function (): void {
                    echo "event: error\ndata: {\"message\":\"Mercure JWT secret is too short. Set MERCURE_JWT_SECRET to at least 32 characters.\"}\n\n";
                    flush();
                },
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/event-stream; charset=UTF-8']
            );
        }
        $topicQuery = implode('&', array_map(
            static fn (string $topic) => 'topic='.rawurlencode($topic),
            $topics
        ));
        $hubUrl = rtrim($mercureInternalUrl, '/').'?'.$topicQuery;

        return new StreamedResponse(
            function () use ($httpClient, $hubUrl, $token): void {
                if (function_exists('apache_setenv')) {
                    @apache_setenv('no-gzip', '1');
                }
                @ini_set('zlib.output_compression', '0');
                @ini_set('output_buffering', 'off');
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }

                try {
                    $response = $httpClient->request('GET', $hubUrl, [
                        'headers' => [
                            'Accept' => 'text/event-stream',
                            'Authorization' => 'Bearer '.$token,
                        ],
                        'buffer' => false,
                        'timeout' => 0,
                    ]);

                    foreach ($httpClient->stream($response) as $chunk) {
                        if ($chunk->isTimeout()) {
                            continue;
                        }
                        if ($chunk->isLast()) {
                            break;
                        }
                        echo $chunk->getContent();
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        if (connection_aborted()) {
                            break;
                        }
                    }
                } catch (\Throwable) {
                    echo "event: error\ndata: {\"message\":\"Unable to reach Mercure hub at ".$hubUrl."\"}\n\n";
                    flush();
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/event-stream; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    #[Route('/counts', name: 'app_admin_realtime_counts', methods: ['GET'])]
    public function counts(
        BookingRepository $bookingRepository,
        CustomerOrderRepository $customerOrderRepository,
        PaymentRepository $paymentRepository,
    ): JsonResponse {
        $totalRevenue = (float) $paymentRepository->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amount), 0)')
            ->where('p.status = :status')
            ->setParameter('status', 'COMPLETED')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'status' => 'success',
            'data' => [
                'pendingBookings' => $bookingRepository->count(['status' => 'PENDING']),
                'pendingOrders' => $customerOrderRepository->countStandalone(['status' => 'PENDING']),
                'totalBookings' => $bookingRepository->count([]),
                'confirmedBookings' => $bookingRepository->count(['status' => 'CONFIRMED']),
                'totalOrders' => $customerOrderRepository->countStandalone(),
                'paidOrders' => $customerOrderRepository->countStandalone(['status' => 'PAID']),
                'totalPayments' => $paymentRepository->count([]),
                'completedPayments' => $paymentRepository->count(['status' => 'COMPLETED']),
                'totalRevenue' => $totalRevenue,
            ],
        ]);
    }

    #[Route('/orders', name: 'app_admin_realtime_orders', methods: ['GET'])]
    public function orders(CustomerOrderRepository $customerOrderRepository): JsonResponse
    {
        $orders = $customerOrderRepository->findStandaloneBy(['createdAt' => 'DESC']);

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn (CustomerOrder $order) => $this->serializeOrder($order), $orders),
        ]);
    }

    #[Route('/bookings', name: 'app_admin_realtime_bookings', methods: ['GET'])]
    public function bookings(BookingRepository $bookingRepository): JsonResponse
    {
        $bookings = $bookingRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn (Booking $booking) => $this->serializeBooking($booking), $bookings),
        ]);
    }

    #[Route('/payments', name: 'app_admin_realtime_payments', methods: ['GET'])]
    public function payments(PaymentRepository $paymentRepository): JsonResponse
    {
        $payments = $paymentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn (Payment $payment) => $this->serializePayment($payment), $payments),
        ]);
    }

    /** @return array<string, mixed> */
    private function serializeOrder(CustomerOrder $order): array
    {
        $user = $order->getUser();
        $product = $order->getProduct();

        return [
            'id' => $order->getId(),
            'customerName' => $user?->getFullName() ?: $user?->getUsername(),
            'customerEmail' => $user?->getEmail(),
            'productName' => $product?->getName(),
            'quantity' => $order->getQuantity(),
            'totalAmount' => $order->getTotalAmount(),
            'status' => $order->getStatus(),
            'createdAt' => $order->getCreatedAt()?->format('M d, Y'),
            'csrfToken' => $this->container->get('security.csrf.token_manager')
                ->getToken('order_status_'.$order->getId())
                ->getValue(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeBooking(Booking $booking): array
    {
        $user = $booking->getUser();
        $product = $booking->getProduct();

        return [
            'id' => $booking->getId(),
            'customerName' => $user?->getFullName() ?: $user?->getUsername(),
            'customerEmail' => $user?->getEmail(),
            'productName' => $product?->getName(),
            'scheduledAt' => $booking->getScheduledAt()?->format('M d, Y g:i A'),
            'status' => $booking->getStatus(),
            'createdAt' => $booking->getCreatedAt()?->format('M d, Y'),
        ];
    }

    /** @return array<string, mixed> */
    private function serializePayment(Payment $payment): array
    {
        $user = $payment->getUser();
        $order = $payment->getOrder();

        return [
            'id' => $payment->getId(),
            'customerName' => $user?->getFullName() ?: $user?->getUsername(),
            'customerEmail' => $user?->getEmail(),
            'orderId' => $order?->getId(),
            'productName' => $order?->getProduct()?->getName(),
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod(),
            'status' => $payment->getStatus(),
            'paidAt' => $payment->getPaidAt()?->format('M d, Y g:i A'),
        ];
    }
}
