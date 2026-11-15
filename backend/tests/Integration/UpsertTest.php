<?php

namespace Tests\Integration;

use App\Domain\Product;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class UpsertTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $this->em = require __DIR__ . '/../bootstrap.php';
    }

    public function testUpsertByExternalCode(): void
    {
        // 1. Первый импорт
        $product = new Product();
        $product->setExternalCode('UPSERT-001');
        $product->setName('Старое имя');
        $product->setPrice('500.00');
        $product->setDiscount('0');
        $this->em->persist($product);
        $this->em->flush();

        // 2. "Повторный импорт" — находим и обновляем
        $existing = $this->em->getRepository(Product::class)->findOneBy(['externalCode' => 'UPSERT-001']);
        $this->assertNotNull($existing);

        $existing->setName('Новое имя');
        $existing->setPrice('600.00');
        $this->em->flush();

        // 3. Проверяем, что запись не задублировалась и обновилась
        $all = $this->em->getRepository(Product::class)->findBy(['externalCode' => 'UPSERT-001']);
        $this->assertCount(1, $all);
        $this->assertSame('Новое имя', $all[0]->getName());
        $this->assertSame('600.00', $all[0]->getPrice());
    }
}
