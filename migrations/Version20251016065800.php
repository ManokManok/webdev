<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251016065800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed phone brand categories based on dashboard brands';
    }

    public function up(Schema $schema): void
    {
        $brands = [
            'Apple','Asus','Google','Huawei','Infinix','Lenovo','Motorola','Nokia','OnePlus','OPPO','realme','Samsung','Sony','TECNO','vivo','Xiaomi'
        ];
        foreach ($brands as $b) {
            $name = str_replace("'", "''", $b);
            // For MySQL/MariaDB we can use INSERT IGNORE to avoid duplicate name errors if unique is later added
            $this->addSql("INSERT INTO category (name) SELECT '$name' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = '$name')");
        }
    }

    public function down(Schema $schema): void
    {
        $brands = [
            'Apple','Asus','Google','Huawei','Infinix','Lenovo','Motorola','Nokia','OnePlus','OPPO','realme','Samsung','Sony','TECNO','vivo','Xiaomi'
        ];
        foreach ($brands as $b) {
            $name = str_replace("'", "''", $b);
            $this->addSql("DELETE FROM category WHERE name = '$name'");
        }
    }
}
