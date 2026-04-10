<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Repository\SupplierRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stock')]
class StockController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    #[Route('/', name: 'app_stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $stockRepository): Response
    {
        $q = $request->query->get('q');
        $sort = strtoupper((string)$request->query->get('sort', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $lowStock = $request->query->get('low_stock');

        if ($lowStock) {
            $stocks = $stockRepository->findLowStock();
        } else {
            $stocks = $stockRepository->search($q, $sort);
        }

        return $this->render('stock/index.html.twig', [
            'stocks' => $stocks,
            'q' => $q,
            'sort' => $sort,
            'low_stock' => $lowStock,
        ]);
    }

    #[Route('/dashboard', name: 'app_stock_dashboard', methods: ['GET'])]
    public function dashboard(StockRepository $stockRepository, SupplierRepository $supplierRepository): Response
    {
        $lowStockItems = $stockRepository->findLowStock();
        $totalInventoryValue = $stockRepository->getTotalInventoryValue();
        $stockBySupplier = $stockRepository->getStockCountBySupplier();

        return $this->render('stock/dashboard.html.twig', [
            'low_stock_items' => $lowStockItems,
            'total_inventory_value' => $totalInventoryValue,
            'stock_by_supplier' => $stockBySupplier,
            'total_items' => count($stockRepository->findAll()),
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setManagedBy($this->getUser());
            $entityManager->persist($stock);
            $entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'CREATE',
                'Stock',
                $stock->getId(),
                sprintf('Created stock item: %s', $stock->getItemName())
            );

            $this->addFlash('success', sprintf('Stock item "%s" has been created successfully.', $stock->getItemName()));
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'Stock',
                $stock->getId(),
                sprintf('Updated stock item: %s', $stock->getItemName())
            );

            $this->addFlash('success', sprintf('Stock item "%s" has been updated successfully.', $stock->getItemName()));
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/adjust', name: 'app_stock_adjust', methods: ['POST'])]
    public function adjustQuantity(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $adjustment = (int) $request->request->get('adjustment', 0);
        $reason = $request->request->get('reason', '');

        if ($adjustment !== 0) {
            $newQuantity = $stock->getQuantity() + $adjustment;
            if ($newQuantity < 0) {
                $this->addFlash('error', 'Stock quantity cannot be negative.');
                return $this->redirectToRoute('app_stock_show', ['id' => $stock->getId()]);
            }
            
            $stock->setQuantity($newQuantity);
            $entityManager->flush();

            $action = $adjustment > 0 ? 'increased' : 'decreased';
            $this->activityLogService->log(
                $this->getUser(),
                'ADJUST',
                'Stock',
                $stock->getId(),
                sprintf('%s stock for %s by %d units. Reason: %s', 
                    $action, 
                    $stock->getItemName(), 
                    abs($adjustment),
                    $reason
                )
            );

            $this->addFlash('success', sprintf('Stock quantity %s by %d units.', $action, abs($adjustment)));
        }

        return $this->redirectToRoute('app_stock_show', ['id' => $stock->getId()]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), $request->request->get('_token'))) {
            $stockId = $stock->getId();
            $stockName = $stock->getItemName();
            
            $entityManager->remove($stock);
            $entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'DELETE',
                'Stock',
                $stockId,
                sprintf('Deleted stock item: %s', $stockName)
            );

            $this->addFlash('success', sprintf('Stock item "%s" has been deleted successfully.', $stockName));
        }

        return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
