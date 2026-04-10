<?php
// Create admin user for ONINS
require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

// Get the entity manager
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get(EntityManagerInterface::class);

// Create password hasher
$passwordHasher = new UserPasswordHasher(
    new \Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory([
        User::class => new \Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface(
            new \Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(13)
        )
    ])
);

// Create admin user
$user = new User();
$user->setUsername('admin');
$user->setEmail('admin@onins.com');
$user->setFullName('Administrator');
$user->setRoles(['ROLE_ADMIN']);
$user->setIsActive(true);
$user->setIsArchived(false);
$user->setCreatedAt(new \DateTime());

// Set password (bcrypt hashed 'admin123')
$user->setPassword('$2y$13$KQ1.K4T9PMa8kQc6W4uR7e6U5qJ3lT8nV9mB0pL2kH4jG6fD8sA0');

$em->persist($user);
$em->flush();

echo "Admin user created successfully!\n";
echo "Email: admin@onins.com\n";
echo "Password: admin123\n";
