<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link customer orders to bookings for mobile pay-after-book flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order ADD booking_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_268F39293301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_268F39293301C60 ON customer_order (booking_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_268F39293301C60');
        $this->addSql('DROP INDEX UNIQ_268F39293301C60 ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP booking_id');
    }
}
