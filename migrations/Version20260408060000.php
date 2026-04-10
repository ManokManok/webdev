<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add email verification fields to user table
 */
final class Version20260408060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_verified and verification_token columns to user table';
    }

    public function up(Schema $schema): void
    {
        // Add is_verified column with default false
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL');
        // Add verification_token column, nullable
        $this->addSql('ALTER TABLE user ADD verification_token VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the columns if we need to rollback
        $this->addSql('ALTER TABLE user DROP is_verified');
        $this->addSql('ALTER TABLE user DROP verification_token');
    }
}
