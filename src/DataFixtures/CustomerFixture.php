<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['customer'];
    }

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {}

    public function load(ObjectManager $manager): void
    {
        if ($this->userRepository->findOneBy(['email' => 'customer@onins.com'])) {
            return;
        }

        $customer = new User();
        $customer->setUsername('customer@onins.com');
        $customer->setEmail('customer@onins.com');
        $customer->setFullName('Demo Customer');
        $customer->setRoles(['ROLE_USER']);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, 'customer123'));
        $customer->setIsActive(true);
        $customer->setIsVerified(true);
        $manager->persist($customer);
        $manager->flush();
    }
}
