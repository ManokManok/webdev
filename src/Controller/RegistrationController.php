<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * RegistrationController handles user registration
 * 
 * @Route("/register", name="app_register")
 */
#[Route('/register', name: 'app_register')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Check if the email domain is allowed for registration
     */
    private function isAllowedEmailDomain(string $email): bool
    {
        $allowedDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com', 'protonmail.com', 'zoho.com', 'gmx.com', 'mail.com', 'live.com', 'msn.com', 'comcast.net', 'verizon.net', 'att.net', 'me.com', 'mac.com', 'fastmail.com', 'tutanota.com', 'qq.com', '163.com', '126.com', 'sina.com', 'sohu.com', 'yeah.net', 'foxmail.com'];
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return in_array($domain, $allowedDomains, true);
    }

    /**
     * Display registration form and handle registration
     * 
     * @Route("", name="", methods={"GET", "POST"})
     */
    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function register(Request $request, EmailVerificationService $emailVerificationService): Response
    {
        // If user is already logged in, redirect to dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            // Check if email domain is allowed
            if (!$this->isAllowedEmailDomain($email)) {
                $this->addFlash('error', 'Please use a valid email address from a recognized provider (e.g., Gmail, Yahoo, Outlook).');
                return $this->redirectToRoute('app_register');
            }

            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);

            // Set admin role for all registrations
            $user->setRoles(['ROLE_ADMIN']);

            // Generate verification token and set user as unverified
            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            // Save user
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Generate verification URL
            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            // Send verification email
            $emailSent = $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            if ($emailSent) {
                $this->addFlash('success', 'Registration successful! Please check your email (including spam folder) to verify your account.');
            } else {
                $this->addFlash('warning', 'Registration successful but email could not be sent. Please contact support.');
            }

            // Redirect to login page
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}

