<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-nino-seed',
    description: 'Import catalog and demo users from the nino database SQL seed (Railway / empty DB).',
)]
class ImportNinoSeedCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'SQL seed file path (relative to project root or absolute)',
                'data/seeds/app-fixture-nino-railway.sql',
            )
            ->addOption('force', null, InputOption::VALUE_NONE, 'Import even if products already exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getOption('file');
        if (!str_starts_with($file, '/') && !preg_match('#^[A-Za-z]:\\\\#', $file)) {
            $file = $this->projectDir . '/' . ltrim($file, '/');
        }

        if (!is_readable($file)) {
            $io->error(sprintf('Seed file not found or not readable: %s', $file));

            return Command::FAILURE;
        }

        if (!$input->getOption('force')) {
            $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM product');
            if ($count > 0) {
                $io->writeln('Product catalog is not empty — skipping nino seed (use --force to override).');

                return Command::SUCCESS;
            }
        }

        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            $io->error('Seed file is empty.');

            return Command::FAILURE;
        }

        $io->writeln(sprintf('Importing nino seed from %s ...', basename($file)));

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        try {
            if ($input->getOption('force')) {
                $this->clearCatalogTables();
            }
            $this->runSqlBatch($sql);
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $products = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM product');
        $users = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user');
        $io->success(sprintf('Nino seed imported (%d products, %d users).', $products, $users));

        return Command::SUCCESS;
    }

    private function clearCatalogTables(): void
    {
        foreach (
            [
                'activity_log',
                'booking',
                'payment',
                'customer_order',
                'stock',
                'product',
                'category',
                'supplier',
                'user',
            ] as $table
        ) {
            try {
                $this->connection->executeStatement(sprintf('DELETE FROM `%s`', $table));
            } catch (\Throwable) {
                // Table may not exist on older schemas.
            }
        }
    }

    private function runSqlBatch(string $sql): void
    {
        // Strip mysqldump comments and conditional version blocks.
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
        $sql = preg_replace('/\/\*![0-9]+.*?\*\/;?/s', '', $sql) ?? $sql;
        $sql = preg_replace('/LOCK TABLES.*?UNLOCK TABLES;/s', '', $sql) ?? $sql;

        foreach (preg_split('/;\s*\R/', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            $upper = strtoupper(ltrim($statement));
            if (str_starts_with($upper, 'SET @') || str_starts_with($upper, 'SET NAMES')) {
                continue;
            }
            $this->connection->executeStatement($statement);
        }
    }
}
