<?php

namespace App\Command;

use App\Service\FcmNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:fcm:status',
    description: 'Check whether Firebase Cloud Messaging is configured for push notifications',
)]
class FcmStatusCommand extends Command
{
    public function __construct(
        private readonly FcmNotifier $fcm,
        #[Autowire('%env(default::FIREBASE_PROJECT_ID)%')]
        private readonly ?string $projectId = '',
        #[Autowire('%env(default::FIREBASE_CREDENTIALS_PATH)%')]
        private readonly ?string $credentialsPath = '',
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectId = trim((string) ($this->projectId ?? ''));
        $credentialsPath = trim((string) ($this->credentialsPath ?? ''));

        $io->title('Firebase Cloud Messaging');

        if ($projectId === '') {
            $io->error('FIREBASE_PROJECT_ID is not set in .env');
        } else {
            $io->writeln(sprintf('Project ID: <info>%s</info>', $projectId));
        }

        if ($credentialsPath === '') {
            $io->error('FIREBASE_CREDENTIALS_PATH is not set in .env');
        } else {
            $resolved = str_contains($credentialsPath, '%kernel.project_dir%')
                ? str_replace('%kernel.project_dir%', dirname(__DIR__, 2), $credentialsPath)
                : $credentialsPath;
            $exists = is_file($resolved);
            $io->writeln(sprintf(
                'Credentials: %s %s',
                $resolved,
                $exists ? '<info>(found)</info>' : '<error>(missing)</error>'
            ));
        }

        if ($this->fcm->isConfigured()) {
            $io->success('FCM is ready — order approve/reject will send system push notifications.');

            return Command::SUCCESS;
        }

        $io->warning('FCM is not fully configured. Copy config/firebase-credentials.json.example to config/firebase-credentials.json and set FIREBASE_* in .env.');

        return Command::FAILURE;
    }
}
