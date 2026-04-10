<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325013931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__stock AS SELECT id, supplier_id, item_name, quantity, min_threshold, unit, description, created_at, updated_at FROM stock');
        $this->addSql('DROP TABLE stock');
        $this->addSql('CREATE TABLE stock (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, supplier_id INTEGER NOT NULL, managed_by_id INTEGER DEFAULT NULL, item_name VARCHAR(255) NOT NULL, quantity INTEGER NOT NULL, min_threshold INTEGER DEFAULT NULL, unit VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , sku VARCHAR(50) DEFAULT NULL, unit_cost NUMERIC(10, 2) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, CONSTRAINT FK_4B3656602ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4B365660873649CA FOREIGN KEY (managed_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO stock (id, supplier_id, item_name, quantity, min_threshold, unit, description, created_at, updated_at) SELECT id, supplier_id, item_name, quantity, min_threshold, unit, description, created_at, updated_at FROM __temp__stock');
        $this->addSql('DROP TABLE __temp__stock');
        $this->addSql('CREATE INDEX IDX_4B3656602ADD6D8C ON stock (supplier_id)');
        $this->addSql('CREATE INDEX IDX_4B365660873649CA ON stock (managed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__stock AS SELECT id, supplier_id, item_name, quantity, min_threshold, unit, description, created_at, updated_at FROM stock');
        $this->addSql('DROP TABLE stock');
        $this->addSql('CREATE TABLE stock (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, supplier_id INTEGER NOT NULL, item_name VARCHAR(255) NOT NULL, quantity INTEGER NOT NULL, min_threshold INTEGER DEFAULT NULL, unit VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_4B3656602ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO stock (id, supplier_id, item_name, quantity, min_threshold, unit, description, created_at, updated_at) SELECT id, supplier_id, item_name, quantity, min_threshold, unit, description, created_at, updated_at FROM __temp__stock');
        $this->addSql('DROP TABLE __temp__stock');
        $this->addSql('CREATE INDEX IDX_4B3656602ADD6D8C ON stock (supplier_id)');
    }
}
