<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure product table has category_id, supplier_id, and created_by_id columns with FK constraints';
    }

    public function up(Schema $schema): void
    {
        $db = $this->connection->getDatabase();

        $columns = $this->connection->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'product'",
            [$db]
        );
        $existing = array_column($columns, 'COLUMN_NAME');

        if (!in_array('category_id', $existing, true)) {
            $this->addSql('ALTER TABLE product ADD category_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADE6ADA943 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_D34A04ADE6ADA943 ON product (category_id)');
        }

        if (!in_array('supplier_id', $existing, true)) {
            $this->addSql('ALTER TABLE product ADD supplier_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_D34A04AD2ADD6D8C ON product (supplier_id)');
        }

        if (!in_array('created_by_id', $existing, true)) {
            $this->addSql('ALTER TABLE product ADD created_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_D34A04ADB03A8386 ON product (created_by_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADE6ADA943');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386');
        $this->addSql('ALTER TABLE product DROP INDEX IDX_D34A04ADE6ADA943');
        $this->addSql('ALTER TABLE product DROP INDEX IDX_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product DROP INDEX IDX_D34A04ADB03A8386');
        $this->addSql('ALTER TABLE product DROP COLUMN category_id');
        $this->addSql('ALTER TABLE product DROP COLUMN supplier_id');
        $this->addSql('ALTER TABLE product DROP COLUMN created_by_id');
    }
}
