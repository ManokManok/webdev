<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * LoginController handles user authentication
 * 
 * Routes:
 * - /login (GET, POST) - Login form and processing
 * - /logout (GET) - User logout
 */
class LoginController extends AbstractController
{
    // Login route is handled by SecurityController to avoid routing conflicts
    // This controller only handles logout

    /**
     * Logout route
     * 
     * Note: This method can be empty - Symfony's firewall handles logout
     * The logout is configured in security.yaml
     * 
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }
}

