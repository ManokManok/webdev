<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\ActivityLogService;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private ActivityLogService $activityLogService,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // If user is already logged in, redirect based on role
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            if ($user->isAdmin()) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            if ($user->isStaff()) {
                return $this->redirectToRoute('app_staff_dashboard');
            }
            // Redirect customers to landing page
            return $this->redirectToRoute('app_home');
        }

        // If this is a POST request to /login (from cached forms), 
        // just render the page - security system won't process it (check_path is app_login_check)
        // This prevents 405 errors from old cached forms

        // Create registration form for display
        $user = new User();
        $registrationForm = $this->createForm(RegistrationType::class, $user);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $registrationForm->createView(),
        ]);
    }

    #[Route('/login_check', name: 'app_login_check', methods: ['GET', 'POST'])]
    public function loginCheck(): Response
    {
        // This route is handled by Symfony's security system
        // GET requests should redirect to login page
        // POST requests are intercepted by the firewall
        return $this->redirectToRoute('app_login');
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    #[Route('/register-admin', name: 'app_register_admin', methods: ['POST'])]
    public function register(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        EmailVerificationService $emailVerificationService,
    ): Response {
        // If user is already logged in, redirect based on role
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            if ($user->isAdmin()) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            if ($user->isStaff()) {
                return $this->redirectToRoute('app_staff_dashboard');
            }
            // Redirect customers to landing page
            return $this->redirectToRoute('app_home');
        }

        // Handle registration form
        $user = new User();
        $routeName = $request->attributes->get('_route');
        if ($routeName === 'app_register_admin' && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['status' => 'error', 'message' => 'Admin registration requires admin privileges.'], Response::HTTP_FORBIDDEN);
        }

        $registrationForm = $this->createForm(RegistrationType::class, $user);
        $registrationForm->handleRequest($request);

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            // Username is already set from the form (now using username field instead of fullName)
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $registrationForm->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Set default role as ROLE_USER for customer accounts
            // Admins and staff should be created through the admin panel
            $user->setRoles(['ROLE_USER']);

            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $emailSent = $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            if ($emailSent) {
                $this->addFlash(
                    'success',
                    'Welcome to Onin\'s Smartphone Repair! Your account was created. Check your email (including spam) to verify before signing in.'
                );
            } else {
                $this->addFlash(
                    'warning',
                    'Welcome to Onin\'s Smartphone Repair! Your account was created, but the verification email could not be sent. Please contact support.'
                );
            }

            return $this->redirectToRoute('app_login');
        }

        // If form has errors, render the login page with the form errors
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $registrationForm->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Note: Logout logging should be handled via an event subscriber
        // (LogoutEvent) as the user session is destroyed after this method
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
