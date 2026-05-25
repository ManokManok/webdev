<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create booking, customer_order, and payment tables for customer API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, scheduled_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_E00CEDDEA76ED395 (user_id), INDEX IDX_E00CEDDE4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_order (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, total_amount DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_268F3929A76ED395 (user_id), INDEX IDX_268F39294584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, order_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, method VARCHAR(64) NOT NULL, status VARCHAR(50) NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_6D28895DA76ED395 (user_id), INDEX IDX_6D28895D8D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_268F3929A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_268F39294584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28895DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28895D8D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28895DA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28895D8D9F6D38');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_268F3929A76ED395');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_268F39294584665A');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEA76ED395');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE4584665A');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE booking');
    }
}
