<?php

require dirname(__DIR__).'/vendor/autoload.php';

use App\Realtime\RealtimeTopics;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$secret = (string) $_ENV['MERCURE_JWT_SECRET'];
$factory = new LcobucciFactory($secret);
$topics = RealtimeTopics::forAdmin();
$token = $factory->create($topics, []);

$hubUrl = rtrim((string) $_ENV['MERCURE_PUBLIC_URL'], '/');
$topicQuery = implode('&', array_map(static fn (string $t) => 'topic='.rawurlencode($t), $topics));
$url = $hubUrl.'?'.$topicQuery.'&authorization='.rawurlencode($token);

echo "Hub: $hubUrl\n";
echo "Token bytes: ".strlen($token)."\n";

$parts = explode('.', $token);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
echo "JWT mercure claim: ".json_encode($payload['mercure'] ?? null)."\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: text/event-stream'],
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HEADER => true,
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "SSE HTTP status: $status\n";
echo substr((string) $response, 0, 800)."\n";
