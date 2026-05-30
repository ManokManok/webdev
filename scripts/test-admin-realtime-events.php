<?php

require dirname(__DIR__).'/vendor/autoload.php';

use App\Entity\User;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();
$user = $em->getRepository(User::class)->findOneBy(['email' => 'admin@onins']);
if (!$user instanceof User) {
    fwrite(STDERR, "Admin user not found\n");
    exit(1);
}

$tokenStorage = $container->get('security.token_storage');
$tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

$request = Request::create('/admin/realtime/events', 'GET');
$response = $kernel->handle($request);

echo 'Events endpoint HTTP: '.$response->getStatusCode()."\n";
echo 'Content-Type: '.$response->headers->get('Content-Type')."\n";

if ($response->getStatusCode() !== 200) {
    echo substr((string) $response->getContent(), 0, 300)."\n";
    exit(1);
}

$callback = $response->getCallback();
if (!is_callable($callback)) {
    fwrite(STDERR, "No stream callback\n");
    exit(1);
}

ob_start();
$callback();
$body = ob_get_clean();
echo 'Stream bytes: '.strlen($body)."\n";
echo 'Stream preview: '.substr($body, 0, 200)."\n";

$kernel->terminate($request, $response);
