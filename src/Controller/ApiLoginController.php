<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ApiLoginController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        private readonly string $googleClientId,
    ) {
    }
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Email and password are required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => trim($data['email'])]);

        if (!$user instanceof User) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isVerified()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please verify your email address before logging in.',
            ], Response::HTTP_FORBIDDEN);
        }

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
            ],
        ]);
    }

    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['POST'])]
    public function googleAuth(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data['idToken'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Google ID token is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $googleProfile = $this->verifyGoogleIdToken((string) $data['idToken']);
        if ($googleProfile === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid or expired Google sign-in. Please try again.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $googleId = $googleProfile['sub'] ?? '';
        $email = trim($googleProfile['email'] ?? '');

        if ($googleId === '' || $email === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Google account email is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['googleId' => $googleId]);
        if (!$user instanceof User) {
            $user = $userRepository->findOneBy(['email' => $email]);
        }

        if ($user instanceof User) {
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'This account must use the web dashboard. The mobile app is for customers only.',
                ], Response::HTTP_FORBIDDEN);
            }

            if (!$user->getGoogleId()) {
                $user->setGoogleId($googleId);
            }
            if (!$user->isVerified()) {
                $user->setIsVerified(true);
            }
            $entityManager->flush();
        } else {
            $user = new User();
            $user->setEmail($email);
            $username = explode('@', $email)[0];
            if ($userRepository->findOneBy(['username' => $username])) {
                $username = $username . '_' . substr($googleId, 0, 8);
            }
            $user->setUsername($username);
            $firstName = trim($googleProfile['given_name'] ?? '');
            $lastName = trim($googleProfile['family_name'] ?? '');
            $fullName = trim($firstName . ' ' . $lastName);
            $user->setFullName($fullName !== '' ? $fullName : $username);
            $user->setGoogleId($googleId);
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $user->setIsActive(true);
            $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
            $entityManager->persist($user);
            $entityManager->flush();
        }

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
            ],
        ]);
    }

    /**
     * @return array<string, string>|null
     */
    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $payload = json_decode($response, true);
        if (!is_array($payload) || isset($payload['error'])) {
            return null;
        }

        $audience = $payload['aud'] ?? '';
        if ($audience !== $this->googleClientId) {
            return null;
        }

        return $payload;
    }
}
