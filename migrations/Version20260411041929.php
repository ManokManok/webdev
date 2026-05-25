<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411041929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, supplier_id INT NOT NULL, managed_by_id INT DEFAULT NULL, item_name VARCHAR(255) NOT NULL, quantity INT NOT NULL, min_threshold INT DEFAULT NULL, unit VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', sku VARCHAR(50) DEFAULT NULL, unit_cost NUMERIC(10, 2) DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, INDEX IDX_4B3656602ADD6D8C (supplier_id), INDEX IDX_4B365660873649CA (managed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B3656602ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660873649CA FOREIGN KEY (managed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD verification_token VARCHAR(64) DEFAULT NULL, ADD google_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B3656602ADD6D8C');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660873649CA');
        $this->addSql('DROP TABLE stock');
        $this->addSql('ALTER TABLE user DROP is_verified, DROP verification_token, DROP google_id');
    }
}
