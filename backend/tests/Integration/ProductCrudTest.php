<?php

namespace Tests\Integration;

use App\Domain\Product;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class ProductCrudTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = require __DIR__ . '/../bootstrap.php';
    }

    public function testCreateProduct(): void
    {
        $product = new Product();
        $product->setExternalCode('TEST-CRUD-001');
        $product->setName('Тестовый товар');
        $product->setPrice('1000.00');
        $product->setDiscount('10.00');
        $product->setDescription('Описание');

        $this->em->persist($product);
        $this->em->flush();

        $found = $this->em->getRepository(Product::class)->findOneBy(['externalCode' => 'TEST-CRUD-001']);

        $this->assertNotNull($found);
        $this->assertSame('Тестовый товар', $found->getName());
        $this->assertSame('1000.00', $found->getPrice());
    }

    public function testUpdateProduct(): void
    {
        // Создаём товар прямо в этом тесте (чтобы не зависеть от testCreateProduct)
        $product = new Product();
        $product->setExternalCode('TEST-CRUD-002');
        $product->setName('Старое имя');
        $product->setPrice('500.00');
        $product->setDiscount('0');
        $this->em->persist($product);
        $this->em->flush();

        // Обновляем
        $product->setName('Новое имя');
        $product->setPrice('600.00');
        $this->em->flush();
        $this->em->refresh($product);

        $this->assertSame('Новое имя', $product->getName());
        $this->assertSame('600.00', $product->getPrice());
    }

    public function testDeleteProduct(): void
    {
        // Создаём товар прямо в этом тесте
        $product = new Product();
        $product->setExternalCode('TEST-CRUD-003');
        $product->setName('Для удаления');
        $product->setPrice('100.00');
        $product->setDiscount('0');
        $this->em->persist($product);
        $this->em->flush();

        // Удаляем
        $this->em->remove($product);
        $this->em->flush();

        $found = $this->em->getRepository(Product::class)->findOneBy(['externalCode' => 'TEST-CRUD-003']);
        $this->assertNull($found);
    }
}
