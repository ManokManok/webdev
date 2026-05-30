<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders', name: 'app_admin_order_')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(
        Request $request,
        CustomerOrder $order,
        EntityManagerInterface $entityManager,
    ): Response {
        return $this->updateStatus($request, $order, 'APPROVED', $entityManager);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(
        Request $request,
        CustomerOrder $order,
        EntityManagerInterface $entityManager,
    ): Response {
        return $this->updateStatus($request, $order, 'REJECTED', $entityManager);
    }

    private function updateStatus(
        Request $request,
        CustomerOrder $order,
        string $newStatus,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('order_status_'.$order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('app_admin_orders');
        }

        $current = strtoupper((string) $order->getStatus());
        if ($current === 'PAID') {
            $this->addFlash('warning', 'Paid orders cannot be changed.');

            return $this->redirectToRoute('app_admin_orders');
        }

        if ($current === $newStatus) {
            return $this->redirectToRoute('app_admin_orders');
        }

        $order->setStatus($newStatus);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Order #%d marked as %s.', $order->getId(), $newStatus));

        return $this->redirectToRoute('app_admin_orders');
    }
}
