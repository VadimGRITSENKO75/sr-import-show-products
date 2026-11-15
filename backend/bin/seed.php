<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Domain\Product;
use App\Domain\ProductAttribute;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;

$config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../src/Domain'], true);
$connection = DriverManager::getConnection([
    'driver' => 'pdo_pgsql',
    'host' => '127.0.0.1',
    'dbname' => 'postgres',
    'user' => 'postgres',
    'password' => 'your_password',
], $config);

$em = new EntityManager($connection, $config);

// Очистка
$em->createQuery('DELETE FROM App\Domain\ProductAttribute a')->execute();
$em->createQuery('DELETE FROM App\Domain\ProductImage i')->execute();
$em->createQuery('DELETE FROM App\Domain\Product p')->execute();

// Товар 1
$p1 = new Product();
$p1->setExternalCode('SKU-001');
$p1->setName('Тестовый товар 1');
$p1->setDescription('Описание первого товара');
$p1->setPrice('1000.00');
$p1->setDiscount('15.00');

$attr = new ProductAttribute();
$attr->setProduct($p1);
$attr->setKey('Доп. поле: Цвет');
$attr->setValue('Красный');
$em->persist($attr);

$em->persist($p1);

// Товар 2
$p2 = new Product();
$p2->setExternalCode('SKU-002');
$p2->setName('Тестовый товар 2');
$p2->setPrice('2500.00');
$p2->setDiscount('10.00');
$em->persist($p2);

$em->flush();
echo "Fixtures loaded: 2 products\n";