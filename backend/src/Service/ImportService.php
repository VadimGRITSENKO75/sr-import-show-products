<?php

namespace App\Service;

use App\Domain\Product;
use App\Domain\ProductAttribute;
use App\Domain\ProductImage;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Client $httpClient,
        private string $uploadDir,
        private string $imageDir,
    ) {
    }

    public function importFromFile(string $filePath, string $taskId): void
    {
        $errors = [];
        $imported = 0;

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // toArray с ключами-буквами колонок (A, B, C...)
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    continue;
                } // Пропускаем заголовки

                // Каждая строка — своя транзакция (атомарность)
                $this->em->beginTransaction();
                try {
                    $this->processRow($row, $sheet, $rowIndex);
                    $this->em->flush();
                    $this->em->commit();
                    $imported++;
                } catch (\Exception $e) {
                    $this->em->rollback();
                    $errors[] = "Строка {$rowIndex}: " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Критическая ошибка: ' . $e->getMessage();
        }

        // Формируем отчёт
        $report = [
            'taskId' => $taskId,
            'status' => empty($errors) ? 'done' : 'done_with_errors',
            'imported' => $imported,
            'errors' => $errors,
            'createdAt' => date('c'),
        ];

        file_put_contents(
            $this->uploadDir . '/report_' . $taskId . '.json',
            json_encode($report, JSON_UNESCAPED_UNICODE)
        );
    }

    private function processRow(array $row, $sheet, int $rowIndex): void
    {
        // Внешний код — колонка F
        $externalCode = trim((string)($row['F'] ?? ''));
        if ($externalCode === '') {
            throw new \InvalidArgumentException('Пустой external_code (колонка F)');
        }

        // Upsert по external_code
        $product = $this->em->getRepository(Product::class)
            ->findOneBy(['externalCode' => $externalCode]);

        if (!$product) {
            $product = new Product();
            $product->setExternalCode($externalCode);
            $this->em->persist($product);
        }

        // Наименование — колонка E
        $product->setName(trim((string)($row['E'] ?? '')));

        // Описание — колонка K
        $description = trim((string)($row['K'] ?? ''));
        $product->setDescription($description !== '' ? $description : null);

        // Цена продажи — колонка I
        $price = $this->parseNumber($row['I'] ?? '0');
        $product->setPrice(number_format($price, 2, '.', ''));

        // Закупочная цена — колонка L
        $purchasePrice = $this->parseNumber($row['L'] ?? '0');

        // Скидка = (Цена продажи − Закупочная) / Цена продажи × 100
        $discount = $price > 0 ? (($price - $purchasePrice) / $price * 100) : 0;
        $product->setDiscount(number_format(round($discount, 2), 2, '.', ''));

        // --- Доп. атрибуты ---
        // Используем жёстко заданные колонки для надёжности
        $attributeColumns = [
            'AG' => 'Доп. поле: Размер',
            'AH' => 'Доп. поле: Цвет',
            'AI' => 'Доп. поле: Бренд',
            'AJ' => 'Доп. поле: Состав',
            'AK' => 'Доп. поле: Кол-во в упаковке',
            'AL' => 'Доп. поле: Ссылка на упаковку',
            'AN' => 'Доп. поле: seo title',
            'AO' => 'Доп. поле: seo h1',
            'AP' => 'Доп. поле: seo description',
            'AQ' => 'Доп. поле: Вес товара(г)',
            'AR' => 'Доп. поле: Ширина(мм)',
            'AS' => 'Доп. поле: Высота(мм)',
            'AT' => 'Доп. поле: Длина(мм)',
            'AU' => 'Доп. поле: Вес упаковки(г)',
            'AV' => 'Доп. поле: Ширина упаковки(мм)',
            'AW' => 'Доп. поле: Высота упаковки(мм)',
            'AX' => 'Доп. поле: Длина упаковки(мм)',
        ];

        foreach ($attributeColumns as $col => $key) {
            $value = $row[$col] ?? null;
            if ($value !== null && trim((string)$value) !== '') {
                $attr = new ProductAttribute();
                $attr->setProduct($product);
                $attr->setKey($key);
                $attr->setValue(trim((string)$value));
                $this->em->persist($attr);
            }
        }

        // --- Изображения (колонка AM) ---
        $imagesRaw = trim((string)($row['AM'] ?? ''));
        if ($imagesRaw !== '') {
            $urls = array_map('trim', explode(',', $imagesRaw));
            foreach ($urls as $url) {
                if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }
                try {
                    $localPath = $this->downloadImage($url);
                    $img = new ProductImage();
                    $img->setProduct($product);
                    $img->setUrl($url);
                    $img->setPath($localPath);
                    $this->em->persist($img);
                } catch (\Exception $e) {
                    // Игнорируем ошибки скачивания конкретного изображения,
                    // чтобы не прерывать импорт всей строки
                }
            }
        }
    }

    /**
     * Парсит число из формата "1320,00" или "1320.00"
     */
    private function parseNumber($value): float
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 0.0;
        }
        // Заменяем запятую на точку
        $value = str_replace(',', '.', $value);
        // Удаляем всё, кроме цифр, точки и минуса
        $value = preg_replace('/[^0-9.\-]/', '', $value);
        return (float)$value;
    }

    /**
     * Скачивает изображение по URL и сохраняет локально
     */
    private function downloadImage(string $url): string
    {
        $filename = uniqid('img_', true) . '.jpg';
        $path = $this->imageDir . '/' . $filename;

        try {
            $this->httpClient->get($url, ['sink' => $path, 'timeout' => 30]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Не удалось скачать изображение: {$url}");
        }

        return '/uploads/' . $filename;
    }
}
