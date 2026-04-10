<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use App\Repository\SupplierRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AdminController - Admin-only protected routes
 * 
 * All routes require ROLE_ADMIN
 * 
 * @Route("/admin", name="app_admin_")
 */
#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * Admin dashboard - only accessible by ROLE_ADMIN
     * 
     * @Route("", name="dashboard", methods={"GET"})
     */
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        ProductRepository $productRepository,
        UserRepository $userRepository,
        ActivityLogRepository $activityLogRepository,
        CategoryRepository $categoryRepository,
        SupplierRepository $supplierRepository,
        StockRepository $stockRepository
    ): Response {
        // Total counts
        $totalUsers = $userRepository->count([]);
        $totalStaff = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_STAFF%')
            ->getQuery()
            ->getSingleScalarResult();
        $totalProducts = $productRepository->count([]);
        $totalCategories = $categoryRepository->count([]);
        $totalSuppliers = $supplierRepository->count([]);

        // Stock counts
        $allStocks = $stockRepository->findAll();
        $inStockCount = count(array_filter($allStocks, fn($s) => !$s->isLowStock()));
        $lowStockCount = count($stockRepository->findLowStock());

        // Recent activities (last 10)
        $recentActivities = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

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

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
            'totalValue' => $totalValue,
            'recentActivities' => $recentActivities,
            'recent' => $recent,
            'inStockCount' => $inStockCount,
            'lowStockCount' => $lowStockCount,
        ]);
    }
}
