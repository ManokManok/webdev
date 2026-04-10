<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $repo, ProductRepository $productRepo): Response
    {
        $categories = $repo->findAll();
        $countsByCategory = $productRepo->countByCategory();
        // Build brand name counts as fallback using product names
        $names = array_map(static fn(Category $c) => $c->getName(), $categories);
        $brandCounts = $productRepo->countByBrandName($names);

        // Sort categories by active count (desc), falling back to brand name count
        usort($categories, function (Category $a, Category $b) use ($countsByCategory, $brandCounts) {
            $cntA = $countsByCategory[$a->getId()] ?? 0;
            if ($cntA === 0) {
                $cntA = $brandCounts[strtolower($a->getName())] ?? 0;
            }
            $cntB = $countsByCategory[$b->getId()] ?? 0;
            if ($cntB === 0) {
                $cntB = $brandCounts[strtolower($b->getName())] ?? 0;
            }
            // Primary: count desc, Secondary: name asc
            if ($cntA !== $cntB) {
                return $cntB <=> $cntA;
            }
            return strcmp($a->getName(), $b->getName());
        });

        $topIssueByCategory = $productRepo->topIssueByCategory();

        // Get products grouped by category for hover dropdown
        $productsByCategory = [];
        foreach ($categories as $category) {
            $productsByCategory[$category->getId()] = $category->getProducts()->toArray();
        }

        // Check if user is staff (not admin) and render staff template
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $template = $isAdmin ? 'category/index.html.twig' : 'category/index_staff.html.twig';

        return $this->render($template, [
            'categories' => $categories,
            'countsByCategory' => $countsByCategory,
            'brandCounts' => $brandCounts,
            'topIssueByCategory' => $topIssueByCategory,
            'productsByCategory' => $productsByCategory,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Category created');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->renderForm('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Category updated');
            return $this->redirectToRoute('app_category_index');
        }

        return $this->renderForm('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Category deleted');
        }
        return $this->redirectToRoute('app_category_index');
    }
}
