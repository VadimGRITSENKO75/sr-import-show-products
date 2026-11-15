<?php
declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products, product_attributes, product_images tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (
            id SERIAL PRIMARY KEY,
            external_code VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            price NUMERIC(10,2) NOT NULL,
            discount NUMERIC(5,2) NOT NULL
        )');

        $this->addSql('CREATE TABLE product_attributes (
            id SERIAL PRIMARY KEY,
            product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            key VARCHAR(255) NOT NULL,
            value TEXT NOT NULL
        )');

        $this->addSql('CREATE TABLE product_images (
            id SERIAL PRIMARY KEY,
            product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            url VARCHAR(2048) NOT NULL,
            path VARCHAR(2048) NOT NULL
        )');

        $this->addSql('CREATE INDEX idx_product_attributes_product ON product_attributes(product_id)');
        $this->addSql('CREATE INDEX idx_product_images_product ON product_images(product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_images');
        $this->addSql('DROP TABLE product_attributes');
        $this->addSql('DROP TABLE products');
    }
}
