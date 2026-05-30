<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class ApiAdminOrderController extends AbstractController
{
    #[Route('/{id}/status', name: 'api_admin_order_status', methods: ['PATCH', 'PUT'])]
    public function updateStatus(
        Request $request,
        CustomerOrder $order,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = json_decode($request->getContent() ?: '{}', true);
        $newStatus = strtoupper(trim((string) ($data['status'] ?? '')));

        if (!in_array($newStatus, ['APPROVED', 'REJECTED', 'PENDING', 'CANCELLED'], true)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid status. Use APPROVED, REJECTED, PENDING, or CANCELLED.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $current = strtoupper((string) $order->getStatus());
        if ($current === 'PAID') {
            return $this->json([
                'status' => 'error',
                'message' => 'Paid orders cannot be changed.',
            ], Response::HTTP_CONFLICT);
        }

        $order->setStatus($newStatus);
        $entityManager->flush();

        return $this->json([
            'status' => 'success',
            'data' => [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
            ],
        ]);
    }
}
