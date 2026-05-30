<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Remove existing admin user if exists
        $existingAdmin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@onins']);
        if ($existingAdmin) {
            $manager->remove($existingAdmin);
            $manager->flush();
        }

        // Create admin user with email as username
        $admin = new User();
        $admin->setUsername('admin@onins');
        $admin->setEmail('admin@onins');
        $admin->setFullName('Administrator');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setIsVerified(true);
        $admin->setIsActive(true);
        $admin->setIsArchived(false);
        $admin->setCreatedAt(new \DateTime());

        $manager->persist($admin);
        $manager->flush();

        // echo "Admin user created successfully!\n";
        // echo "Email/Username: admin@onins\n";
        // echo "Password: admin123\n";
    }
}
