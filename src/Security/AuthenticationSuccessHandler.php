<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Custom authentication success handler
 * Redirects users to appropriate dashboard based on their role
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        // Get the authenticated user
        $user = $token->getUser();
        
        // Check if user is an instance of User entity
        if ($user instanceof User) {
            // Redirect admins to admin dashboard
            if ($user->isAdmin()) {
                return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
            }
            
            // Redirect staff to staff dashboard
            if ($user->isStaff()) {
                return new RedirectResponse($this->urlGenerator->generate('app_staff_dashboard'));
            }
            
            // Redirect customers to landing page
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }
        
        // Fallback: redirect to landing page
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}

