<?php

namespace App\Controller;

use App\Entity\User;
use App\Realtime\RealtimeTopics;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ApiDeviceController extends AbstractController
{
    #[Route('/devices/fcm-token', name: 'api_devices_fcm_token', methods: ['POST', 'PUT'])]
    #[IsGranted('ROLE_USER')]
    public function registerFcmToken(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['status' => 'error', 'message' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent() ?: '{}', true);
        $token = trim((string) ($data['token'] ?? $data['fcmToken'] ?? ''));

        if ($token === '') {
            return $this->json(['status' => 'error', 'message' => 'FCM token is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setFcmToken($token);
        $entityManager->flush();

        return $this->json(['status' => 'success', 'message' => 'FCM token registered.']);
    }

    #[Route('/realtime/token', name: 'api_realtime_token', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function realtimeToken(
        #[Autowire(service: 'mercure.hub.default.jwt.factory')]
        TokenFactoryInterface $tokenFactory,
        #[Autowire('%env(default::MERCURE_PUBLIC_URL)%')]
        string $mercurePublicUrl,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['status' => 'error', 'message' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($mercurePublicUrl === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'Realtime is not configured on the server (MERCURE_PUBLIC_URL).',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $topics = RealtimeTopics::forCustomer($user->getId());
        $subscriberToken = $tokenFactory->create($topics, []);

        return $this->json([
            'status' => 'success',
            'data' => [
                'hubUrl' => rtrim($mercurePublicUrl, '/'),
                'token' => $subscriberToken,
                'topics' => $topics,
            ],
        ]);
    }
}
