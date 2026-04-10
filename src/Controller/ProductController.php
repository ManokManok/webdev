<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\Product1Type;
use App\Repository\ProductRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * List all products - accessible by all authenticated users (admin and staff)
     * Staff can view and edit all products, including those created by admin
     */
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $q = $request->query->get('q');
        $sort = strtoupper((string)$request->query->get('sort', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        // Return all products - no user-based filtering
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->search($q, $sort),
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(Product1Type::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creator of the product
            $product->setCreatedBy($this->getUser());
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'CREATE',
                'Product',
                $product->getId(),
                sprintf('Created product: %s', $product->getName())
            );

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * Show product details - accessible by all authenticated users
     * Staff can view any product, including those created by admin
     */
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Edit product - Both staff and admin can edit all products
     */
    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Product1Type::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'Product',
                $product->getId(),
                sprintf('Updated product: %s', $product->getName())
            );

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productId = $product->getId();
            $productName = $product->getName();
            $entityManager->remove($product);
            $entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'DELETE',
                'Product',
                $productId,
                sprintf('Deleted product: %s', $productName)
            );

            $this->addFlash('success', sprintf('Product "%s" has been deleted successfully.', $productName));
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
