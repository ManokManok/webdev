<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251016061400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category and supplier tables; add nullable relations to product with FK SET NULL on delete';
    }

    public function up(Schema $schema): void
    {
        // Category table
        if (!$schema->hasTable('category')) {
            $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        // Supplier table
        if (!$schema->hasTable('supplier')) {
            $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, contact VARCHAR(150) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        // Add columns to product if missing
        $this->addSql('ALTER TABLE product ADD category_id INT DEFAULT NULL, ADD supplier_id INT DEFAULT NULL');
        // Indexes
        $this->addSql('CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
        $this->addSql('CREATE INDEX IDX_D34A04ADD2C4C5D6 ON product (supplier_id)');
        // FKs
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADD2C4C5D6 FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADD2C4C5D6');
        $this->addSql('DROP INDEX IDX_D34A04AD12469DE2 ON product');
        $this->addSql('DROP INDEX IDX_D34A04ADD2C4C5D6 ON product');
        $this->addSql('ALTER TABLE product DROP category_id, DROP supplier_id');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('DROP TABLE IF EXISTS supplier');
    }
}
