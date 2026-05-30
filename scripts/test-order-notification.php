<?php

/**
 * Smoke-test: Mercure publish on order status change.
 * Usage: php scripts/test-order-notification.php [orderId] [status]
 */

require dirname(__DIR__).'/vendor/autoload.php';

use App\Entity\CustomerOrder;
use App\Kernel;
use App\Service\CustomerNotificationService;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$orderId = (int) ($argv[1] ?? 28);
$newStatus = strtoupper((string) ($argv[2] ?? 'REJECTED'));

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

/** @var CustomerOrder|null $order */
$order = $em->getRepository(CustomerOrder::class)->find($orderId);
if (!$order) {
    fwrite(STDERR, "Order #$orderId not found\n");
    exit(1);
}

$previous = (string) $order->getStatus();
if ($previous === $newStatus) {
    fwrite(STDERR, "Order #$orderId already $newStatus\n");
    exit(1);
}

$order->setStatus($newStatus);
$em->flush();

echo "Order #$orderId: $previous -> $newStatus (listener should have published Mercure + attempted FCM)\n";
