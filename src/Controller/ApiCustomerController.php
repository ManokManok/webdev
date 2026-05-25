<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CustomerOrderRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ApiCustomerController extends AbstractController
{
    private function parseJsonRequest(Request $request): array
    {
        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload. Please send a valid JSON body.');
        }

        return is_array($data) ? $data : [];
    }

    private function respondError(string $message, int $status = Response::HTTP_BAD_REQUEST, array $errors = []): JsonResponse
    {
        $payload = ['status' => 'error', 'message' => $message];
        if ($errors) {
            $payload['errors'] = $errors;
        }
        return $this->json($payload, $status);
    }

    private function mapUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
        ];
    }

    private function assertCustomerAccount(User $user): ?JsonResponse
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
            return $this->respondError(
                'This account must use the web dashboard. The mobile app is for customers only.',
                Response::HTTP_FORBIDDEN
            );
        }

        return null;
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $data = $this->parseJsonRequest($request);
        } catch (\InvalidArgumentException $exception) {
            return $this->respondError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $errors = [];
        if (!$name) {
            $errors['name'] = 'Name is required.';
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }
        if (!$password || mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }
        if ($email && $userRepository->findOneBy(['email' => $email])) {
            $errors['email'] = 'This email is already registered.';
        }

        if ($errors) {
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($email);
        $user->setFullName($name);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(true);
        $user->setIsActive(true);

        $entityManager->persist($user);
        $entityManager->flush();

        $token = $jwtManager->create($user);

        return $this->json([
            'status' => 'success',
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $this->mapUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/profile', name: 'api_profile', methods: ['GET', 'PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->respondError('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        if ($response = $this->assertCustomerAccount($user)) {
            return $response;
        }

        if ($request->isMethod('GET')) {
            return $this->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                    'roles' => $user->getRoles(),
                    'isVerified' => $user->isVerified(),
                    'createdAt' => $user->getCreatedAt()?->format('c'),
                ],
            ]);
        }

        try {
            $data = $this->parseJsonRequest($request);
        } catch (\InvalidArgumentException $exception) {
            return $this->respondError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $fullName = trim($data['fullName'] ?? $data['name'] ?? '');
        $newPassword = $data['password'] ?? $data['newPassword'] ?? '';
        $currentPassword = $data['currentPassword'] ?? '';

        $errors = [];
        if ($fullName === '') {
            $errors['fullName'] = 'Name is required.';
        }
        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 8) {
                $errors['password'] = 'New password must be at least 8 characters long.';
            }
            if ($currentPassword === '' || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $errors['currentPassword'] = 'Current password is incorrect.';
            }
        }

        if ($errors) {
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setFullName($fullName);
        if ($newPassword !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        $entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
                'createdAt' => $user->getCreatedAt()?->format('c'),
            ],
        ]);
    }

    #[Route('/products', name: 'api_products', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function products(ProductRepository $productRepository): JsonResponse
    {
        $user = $this->getUser();
        if ($user instanceof User && ($response = $this->assertCustomerAccount($user))) {
            return $response;
        }

        $products = $productRepository->findAll();
        $items = array_map(fn(Product $product) => [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'issue' => $product->getIssue(),
            'price' => $product->getPrice(),
            'category' => $product->getCategory()?->getName(),
            'supplier' => $product->getSupplier()?->getName(),
        ], $products);

        return $this->json(['status' => 'success', 'data' => $items]);
    }

    #[Route('/bookings', name: 'api_bookings', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function bookings(
        Request $request,
        BookingRepository $bookingRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->respondError('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        if ($response = $this->assertCustomerAccount($user)) {
            return $response;
        }

        if ($request->isMethod('POST')) {
            try {
                $data = $this->parseJsonRequest($request);
            } catch (\InvalidArgumentException $exception) {
                return $this->respondError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
            }

            $productId = $data['productId'] ?? null;
            $scheduledAt = trim($data['scheduledAt'] ?? '');
            $notes = trim($data['notes'] ?? '');

            $errors = [];
            if (!$productId) {
                $errors['productId'] = 'Product ID is required.';
            }
            if (!$scheduledAt) {
                $errors['scheduledAt'] = 'Booking date is required.';
            }

            $product = $productId ? $productRepository->find($productId) : null;
            if ($productId && !$product) {
                $errors['productId'] = 'Product not found.';
            }

            if ($errors) {
                return $this->json(['status' => 'error', 'message' => 'Validation failed.', 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            try {
                $scheduledDate = new \DateTimeImmutable($scheduledAt);
            } catch (\Exception $exception) {
                return $this->json(['status' => 'error', 'message' => 'Invalid booking date format. Use ISO 8601.'], Response::HTTP_BAD_REQUEST);
            }

            $booking = new Booking();
            $booking->setUser($user);
            $booking->setProduct($product);
            $booking->setScheduledAt($scheduledDate);
            $booking->setNotes($notes);
            $booking->setStatus('PENDING');
            $booking->setCreatedAt(new \DateTimeImmutable());

            $order = new CustomerOrder();
            $order->setUser($user);
            $order->setProduct($product);
            $order->setQuantity(1);
            $order->setTotalAmount($product->getPrice());
            $order->setStatus('PENDING');
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setBooking($booking);

            $entityManager->persist($booking);
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->json([
                'status' => 'success',
                'data' => $this->mapBooking($booking),
                'order' => $this->mapOrder($order),
            ], Response::HTTP_CREATED);
        }

        $bookings = $bookingRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $items = array_map([$this, 'mapBooking'], $bookings);

        return $this->json(['status' => 'success', 'data' => $items]);
    }

    #[Route('/orders', name: 'api_orders', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function orders(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        CustomerOrderRepository $orderRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->respondError('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        if ($response = $this->assertCustomerAccount($user)) {
            return $response;
        }

        if ($request->isMethod('POST')) {
            try {
                $data = $this->parseJsonRequest($request);
            } catch (\InvalidArgumentException $exception) {
                return $this->respondError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
            }

            $productId = $data['productId'] ?? null;
            $quantity = (int)($data['quantity'] ?? 1);

            $errors = [];
            if (!$productId) {
                $errors['productId'] = 'Product ID is required.';
            }
            if ($quantity < 1) {
                $errors['quantity'] = 'Quantity must be at least 1.';
            }

            $product = $productId ? $productRepository->find($productId) : null;
            if ($productId && !$product) {
                $errors['productId'] = 'Product not found.';
            }

            if ($errors) {
                return $this->json(['status' => 'error', 'message' => 'Validation failed.', 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $order = new CustomerOrder();
            $order->setUser($user);
            $order->setProduct($product);
            $order->setQuantity($quantity);
            $order->setTotalAmount($product->getPrice() * $quantity);
            $order->setStatus('PENDING');
            $order->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($order);
            $entityManager->flush();

            return $this->json(['status' => 'success', 'data' => $this->mapOrder($order)], Response::HTTP_CREATED);
        }

        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $items = array_map([$this, 'mapOrder'], $orders);

        return $this->json(['status' => 'success', 'data' => $items]);
    }

    #[Route('/payments', name: 'api_payments', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function payments(
        Request $request,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        CustomerOrderRepository $orderRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->respondError('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        if ($response = $this->assertCustomerAccount($user)) {
            return $response;
        }

        if ($request->isMethod('POST')) {
            try {
                $data = $this->parseJsonRequest($request);
            } catch (\InvalidArgumentException $exception) {
                return $this->respondError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
            }

            $orderId = $data['orderId'] ?? null;
            $amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
            $method = trim($data['method'] ?? 'card');

            $errors = [];
            if (!$orderId) {
                $errors['orderId'] = 'Order ID is required.';
            }
            if ($amount <= 0) {
                $errors['amount'] = 'Payment amount must be greater than zero.';
            }

            $order = $orderId ? $orderRepository->find($orderId) : null;
            if ($orderId && (!$order || $order->getUser()->getId() !== $user->getId())) {
                $errors['orderId'] = 'Order not found or does not belong to the current customer.';
            }
            if ($order && $order->getStatus() === 'PAID') {
                $errors['orderId'] = 'This order has already been paid.';
            }
            if ($order && abs($amount - (float) $order->getTotalAmount()) > 0.01) {
                $errors['amount'] = sprintf(
                    'Payment amount must match the order total (%.2f).',
                    $order->getTotalAmount()
                );
            }

            if ($errors) {
                return $this->json(['status' => 'error', 'message' => 'Validation failed.', 'errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $payment = new Payment();
            $payment->setUser($user);
            $payment->setOrder($order);
            $payment->setAmount($amount);
            $payment->setMethod($method);
            $payment->setStatus('COMPLETED');
            $payment->setPaidAt(new \DateTimeImmutable());
            $payment->setCreatedAt(new \DateTimeImmutable());

            $order->setStatus('PAID');
            $booking = $order->getBooking();
            if ($booking instanceof Booking && $booking->getStatus() === 'PENDING') {
                $booking->setStatus('CONFIRMED');
                $entityManager->persist($booking);
            }
            $entityManager->persist($payment);
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->json(['status' => 'success', 'data' => $this->mapPayment($payment)], Response::HTTP_CREATED);
        }

        $payments = $paymentRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $items = array_map([$this, 'mapPayment'], $payments);

        return $this->json(['status' => 'success', 'data' => $items]);
    }

    private function mapBooking(Booking $booking): array
    {
        $order = $booking->getOrder();

        return [
            'id' => $booking->getId(),
            'product' => [
                'id' => $booking->getProduct()?->getId(),
                'name' => $booking->getProduct()?->getName(),
            ],
            'scheduledAt' => $booking->getScheduledAt()?->format('c'),
            'notes' => $booking->getNotes(),
            'status' => $booking->getStatus(),
            'createdAt' => $booking->getCreatedAt()?->format('c'),
            'order' => $order ? [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'totalAmount' => $order->getTotalAmount(),
            ] : null,
        ];
    }

    private function mapOrder(CustomerOrder $order): array
    {
        $booking = $order->getBooking();

        return [
            'id' => $order->getId(),
            'product' => [
                'id' => $order->getProduct()?->getId(),
                'name' => $order->getProduct()?->getName(),
                'price' => $order->getProduct()?->getPrice(),
            ],
            'quantity' => $order->getQuantity(),
            'totalAmount' => $order->getTotalAmount(),
            'status' => $order->getStatus(),
            'createdAt' => $order->getCreatedAt()?->format('c'),
            'booking' => $booking ? [
                'id' => $booking->getId(),
                'scheduledAt' => $booking->getScheduledAt()?->format('c'),
                'status' => $booking->getStatus(),
            ] : null,
        ];
    }

    private function mapPayment(Payment $payment): array
    {
        return [
            'id' => $payment->getId(),
            'order' => [
                'id' => $payment->getOrder()?->getId(),
                'status' => $payment->getOrder()?->getStatus(),
            ],
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod(),
            'status' => $payment->getStatus(),
            'paidAt' => $payment->getPaidAt()?->format('c'),
            'createdAt' => $payment->getCreatedAt()?->format('c'),
        ];
    }
}
