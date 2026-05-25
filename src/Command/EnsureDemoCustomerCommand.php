<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:ensure-demo-customer',
    description: 'Create demo accounts when missing (safe for production; never deletes data).',
)]
class EnsureDemoCustomerCommand extends Command
{
    /** @var list<array{email: string, username: string, fullName: string, password: string, roles: list<string>}> */
    private const DEMO_ACCOUNTS = [
        [
            'email' => 'customer@onins.com',
            'username' => 'customer@onins.com',
            'fullName' => 'Demo Customer',
            'password' => 'customer123',
            'roles' => ['ROLE_USER'],
        ],
        [
            'email' => 'stockadmin@cabajon.com',
            'username' => 'stockadmin',
            'fullName' => 'Stock Administrator',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN'],
        ],
        [
            'email' => 'stockmanager@cabajon.com',
            'username' => 'stockmanager',
            'fullName' => 'Stock Manager',
            'password' => 'staff123',
            'roles' => ['ROLE_STAFF'],
        ],
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach (self::DEMO_ACCOUNTS as $account) {
            if ($this->userRepository->findOneBy(['email' => $account['email']])) {
                continue;
            }

            $user = new User();
            $user->setUsername($account['username']);
            $user->setEmail($account['email']);
            $user->setFullName($account['fullName']);
            $user->setRoles($account['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $account['password']));
            $user->setIsActive(true);
            $user->setIsVerified(true);

            $this->entityManager->persist($user);
            ++$created;
        }

        if ($created > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Created %d demo account(s).', $created));
        } else {
            $io->writeln('Demo accounts already exist.');
        }

        return Command::SUCCESS;
    }
}
