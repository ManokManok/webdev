<?php

namespace App\Command;

use App\Entity\User;
use App\Realtime\RealtimeTopics;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

#[AsCommand(
    name: 'app:realtime:test-token',
    description: 'Verify Mercure subscriber JWT generation for a customer account',
)]
class TestRealtimeTokenCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        #[Autowire(service: 'mercure.hub.default.jwt.factory')]
        private readonly TokenFactoryInterface $tokenFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = $this->users->findOneBy(['email' => 'customer@onins.com']);
        if (!$user instanceof User) {
            $io->error('Demo customer not found.');

            return Command::FAILURE;
        }

        try {
            $topics = RealtimeTopics::forCustomer((int) $user->getId());
            $token = $this->tokenFactory->create($topics, []);
            $io->success(sprintf('Mercure token OK (%d bytes) for user #%d', strlen($token), $user->getId()));
            $io->listing($topics);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
