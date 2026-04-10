<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DashboardController - Protected route example
 * 
 * Accessible by any authenticated user (ROLE_USER or higher)
 * 
 * @Route("/dashboard", name="app_dashboard")
 */
#[Route('/dashboard', name: 'app_dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    /**
     * User dashboard - accessible by any authenticated user
     * 
     * @Route("", name="", methods={"GET"})
     */
    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        // Get current user
        $user = $this->getUser();

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
        ]);
    }
}

