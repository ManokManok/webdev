<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019120930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD stock INT DEFAULT 0 NOT NULL, ADD reorder_point INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product RENAME INDEX idx_d34a04add2c4c5d6 TO IDX_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE supplier ADD contact_person VARCHAR(150) DEFAULT NULL, ADD company_type VARCHAR(100) DEFAULT NULL, ADD registration_no VARCHAR(150) DEFAULT NULL, ADD warranty_info LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE supplier DROP contact_person, DROP company_type, DROP registration_no, DROP warranty_info');
        $this->addSql('ALTER TABLE product DROP stock, DROP reorder_point');
        $this->addSql('ALTER TABLE product RENAME INDEX idx_d34a04ad2add6d8c TO IDX_D34A04ADD2C4C5D6');
    }
}
