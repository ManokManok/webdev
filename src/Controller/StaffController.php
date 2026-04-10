<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SupplierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * StaffController - Staff-only protected routes
 * 
 * All routes require ROLE_STAFF or ROLE_ADMIN
 * 
 * @Route("/staff", name="app_staff_")
 */
#[Route('/staff', name: 'app_staff_')]
#[IsGranted('ROLE_STAFF')]
class StaffController extends AbstractController
{
    /**
     * Staff dashboard - accessible by ROLE_STAFF and ROLE_ADMIN
     * 
     * @Route("", name="dashboard", methods={"GET"})
     */
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SupplierRepository $supplierRepository
    ): Response {
        // Get current user
        $user = $this->getUser();
        
        // Total counts (staff can see these)
        $totalProducts = $productRepository->count([]);
        $totalCategories = $categoryRepository->count([]);
        $totalSuppliers = $supplierRepository->count([]);

        // Calculate total value (sum of all product prices)
        $totalValue = $productRepository->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.price), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        // Recent products
        $recent = $productRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('staff/dashboard.html.twig', [
            'user' => $user,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
            'totalValue' => $totalValue,
            'recent' => $recent,
        ]);
    }
}

