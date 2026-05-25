<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class ApiAdminController extends AbstractController
{
    #[Route('/users', name: 'api_admin_users', methods: ['GET'])]
    public function users(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        $items = array_map(fn($user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ], $users);

        return $this->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }
}
