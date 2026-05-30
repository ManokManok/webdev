<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    /**
     * @param list<string> $adminEmails
     */
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthenticationSuccessHandler $authenticationSuccessHandler,
        private array $adminEmails = [],
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);
        $isMobile = $this->isMobileOAuth($request);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $isMobile) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);
                if (!$user) {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                }

                if ($user instanceof User) {
                    if (!$user->getGoogleId()) {
                        $user->setGoogleId($googleId);
                    }
                    if (!$user->isVerified()) {
                        $user->setIsVerified(true);
                    }
                    $this->ensureAdminRoleForEmail($user, $email);
                    $this->entityManager->flush();

                    return $user;
                }

                $user = new User();
                $user->setEmail($email);
                $username = explode('@', $email)[0];
                if ($this->entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
                    $username = $username . '_' . substr($googleId, 0, 8);
                }
                $user->setUsername($username);
                $firstName = trim((string) $googleUser->getFirstName());
                $lastName = trim((string) $googleUser->getLastName());
                $fullName = trim($firstName . ' ' . $lastName);
                $user->setFullName($fullName !== '' ? $fullName : $username);
                $user->setGoogleId($googleId);
                $user->setIsVerified(true);
                $user->setIsActive(true);
                $user->setRoles($this->resolveRolesForNewUser($email, $isMobile));
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16)))
                );
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $session = $request->getSession();
        $isMobile = $this->isMobileOAuth($request);
        $session->remove('oauth_platform');

        error_log('Google login successful for user: ' . $user->getEmail());

        if ($isMobile) {
            return $this->createMobileRedirectResponse($user);
        }

        return $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->getSession();
        $isMobile = $this->isMobileOAuth($request);
        $session->remove('oauth_platform');

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        if ($isMobile) {
            return new RedirectResponse($this->buildMobileRedirectUri([
                'status' => 'error',
                'error' => $message !== '' ? $message : 'Google sign-in failed.',
            ]));
        }

        $session->getFlashBag()->add(
            'error',
            $message !== '' ? $message : 'Google sign-in failed. Please try again.'
        );

        return new RedirectResponse($this->router->generate('app_login'));
    }

    private function createMobileRedirectResponse(User $user): RedirectResponse
    {
        if ($this->hasRole($user, 'ROLE_ADMIN') || $this->hasRole($user, 'ROLE_STAFF')) {
            return new RedirectResponse($this->buildMobileRedirectUri([
                'status' => 'error',
                'error' => 'This account must use the web dashboard. The mobile app is for customers only.',
            ]));
        }

        $jwt = $this->jwtManager->create($user);

        return new RedirectResponse($this->buildMobileRedirectUri([
            'status' => 'success',
            'token' => $jwt,
        ]));
    }

    /**
     * @param array<string, string> $params
     */
    private function buildMobileRedirectUri(array $params): string
    {
        return 'onins://oauth?' . http_build_query($params);
    }

    private function hasRole(User $user, string $role): bool
    {
        $userRoles = array_map('strtoupper', $user->getRoles());

        return in_array(strtoupper($role), $userRoles, true);
    }

    private function isMobileOAuth(Request $request): bool
    {
        if ($request->getSession()->get('oauth_platform') === 'mobile') {
            return true;
        }

        $state = (string) $request->query->get('state', '');

        return str_starts_with($state, 'mobile_');
    }

    /**
     * @return list<string>
     */
    private function resolveRolesForNewUser(string $email, bool $isMobile): array
    {
        if ($this->isAdminEmail($email)) {
            return ['ROLE_ADMIN', 'ROLE_USER'];
        }

        return $isMobile ? ['ROLE_USER'] : ['ROLE_STAFF'];
    }

    private function ensureAdminRoleForEmail(User $user, string $email): void
    {
        if ($this->isAdminEmail($email) && !$user->isAdmin()) {
            $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        }
    }

    private function isAdminEmail(string $email): bool
    {
        $normalized = strtolower(trim($email));

        foreach ($this->adminEmails as $adminEmail) {
            if ($normalized === strtolower(trim($adminEmail))) {
                return true;
            }
        }

        return false;
    }
}
