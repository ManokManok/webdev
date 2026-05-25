<?php
namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class GoogleController extends AbstractController
{
    public function __construct(
        private RouterInterface $router,
        #[Autowire('%env(default::GOOGLE_OAUTH_CALLBACK_BASE)%')]
        private string $oauthCallbackBase = '',
    ) {
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(
        Request $request,
        ClientRegistry $clientRegistry,
        SessionInterface $session
    ) {
        $isMobile = $request->query->get('platform') === 'mobile';

        if ($isMobile) {
            $this->applyOAuthCallbackBaseFromRequest($request);
        } else {
            $this->applyOAuthCallbackBase();
        }

        $options = [];

        if ($isMobile) {
            $session->set('oauth_platform', 'mobile');
            // State survives if the session cookie is lost in the mobile auth browser
            $options['state'] = 'mobile_' . bin2hex(random_bytes(16));
        } else {
            $session->remove('oauth_platform');
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], $options);
    }

    /**
     * Forces redirect_uri to match Google Cloud Console (e.g. 127.0.0.1 even when opened via LAN IP).
     */
    private function applyOAuthCallbackBase(): void
    {
        $base = rtrim(trim($this->oauthCallbackBase), '/');
        if ($base === '') {
            return;
        }

        $this->applyRouterContextFromBaseUrl($base);
    }

    /**
     * Mobile OAuth: use the host the app opened (10.0.2.2 on Android emulator, LAN IP on device).
     */
    private function applyOAuthCallbackBaseFromRequest(Request $request): void
    {
        $configured = rtrim(trim($this->oauthCallbackBase), '/');
        if ($configured !== '') {
            $parts = parse_url($configured);
            $host = $request->getHost();
            $isEmulatorHost = $host === '10.0.2.2';
            $isLocalRequest = in_array($host, ['127.0.0.1', 'localhost'], true);

            if (!$isEmulatorHost && !$isLocalRequest && !empty($parts['host'])) {
                $this->applyOAuthCallbackBase();

                return;
            }
        }

        $scheme = $request->isSecure() ? 'https' : 'http';
        $port = $request->getPort();
        $base = $scheme . '://' . $request->getHost();
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $base .= ':' . $port;
        }

        $this->applyRouterContextFromBaseUrl($base);
    }

    private function applyRouterContextFromBaseUrl(string $base): void
    {
        $parts = parse_url($base);
        if ($parts === false || empty($parts['host'])) {
            return;
        }

        $context = $this->router->getContext();
        $scheme = $parts['scheme'] ?? 'http';
        $context->setScheme($scheme);
        $context->setHost($parts['host']);

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($scheme === 'https') {
            $context->setHttpsPort((int) $port);
        } else {
            $context->setHttpPort((int) $port);
        }
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request)
    {
        // This remains empty! The bundle's authenticator will intercept this route.
    }
}