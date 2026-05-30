<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends Firebase Cloud Messaging push notifications (HTTP v1 API).
 */
class FcmNotifier
{
    public const ANDROID_CHANNEL_ORDERS = 'orders';

    private ?array $credentials = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(default::FIREBASE_PROJECT_ID)%')]
        private readonly ?string $projectId = '',
        #[Autowire('%env(default::FIREBASE_CREDENTIALS_PATH)%')]
        private readonly ?string $credentialsPath = '',
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return ($this->projectId ?? '') !== '' && $this->loadCredentials() !== null;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $token = $user->getFcmToken();
        if (!$token || !$this->isConfigured()) {
            return;
        }

        $this->send($token, $title, $body, $data);
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        try {
            $accessToken = $this->getAccessToken();
            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
            }

            $response = $this->httpClient->request('POST', sprintf(
                'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                $this->projectId ?? ''
            ), [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $stringData,
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => self::ANDROID_CHANNEL_ORDERS,
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $status = $response->getStatusCode();
            if ($status >= 400) {
                $this->logger?->warning('FCM HTTP {status}: {body}', [
                    'status' => $status,
                    'body' => $response->getContent(false),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger?->warning('FCM send failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function loadCredentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials ?: null;
        }

        $path = $this->resolveCredentialsPath();
        if ($path === '' || !is_file($path)) {
            $this->credentials = [];

            return null;
        }

        $json = file_get_contents($path);
        $decoded = json_decode($json ?: '', true);
        if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            $this->credentials = [];

            return null;
        }

        $this->credentials = $decoded;

        return $this->credentials;
    }

    private function getAccessToken(): string
    {
        $credentials = $this->loadCredentials();
        if ($credentials === null) {
            throw new \RuntimeException('Firebase credentials are not configured.');
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claim;
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            throw new \RuntimeException('Invalid Firebase private key.');
        }

        openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ]);

        $payload = $response->toArray(false);

        if (empty($payload['access_token'])) {
            throw new \RuntimeException('Unable to obtain Firebase access token.');
        }

        return $payload['access_token'];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function resolveCredentialsPath(): string
    {
        $path = trim((string) ($this->credentialsPath ?? ''));
        if ($path === '') {
            return '';
        }

        if (str_contains($path, '%kernel.project_dir%')) {
            $projectDir = dirname(__DIR__, 2);

            return str_replace('%kernel.project_dir%', $projectDir, $path);
        }

        if (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $projectDir = dirname(__DIR__, 2);

            return $projectDir.'/'.$path;
        }

        return $path;
    }
}
