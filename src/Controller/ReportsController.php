<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReportsController extends AbstractController
{
    #[Route('/reports', name: 'app_reports_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        $count = count($products);
        $totalValue = 0.0;
        $maxPrice = null; $minPrice = null; $avgPrice = 0.0;
        foreach ($products as $p) {
            $price = (float)($p->getPrice() ?? 0);
            $totalValue += $price;
            $maxPrice = $maxPrice === null ? $price : max($maxPrice, $price);
            $minPrice = $minPrice === null ? $price : min($minPrice, $price);
        }
        if ($count > 0) { $avgPrice = $totalValue / $count; }

        // Top 5 most expensive products
        usort($products, fn($a, $b) => ($b->getPrice() ?? 0) <=> ($a->getPrice() ?? 0));
        $top = array_slice($products, 0, 5);

        return $this->render('reports/index.html.twig', [
            'count' => $count,
            'totalValue' => $totalValue,
            'avgPrice' => $avgPrice,
            'maxPrice' => $maxPrice,
            'minPrice' => $minPrice,
            'top' => $top,
        ]);
    }
}
