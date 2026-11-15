<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\Message\ImportProductsMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportController
{
    public function __construct(
        private MessageBusInterface $bus,
        private string $uploadDir,
    ) {
    }


    #[OA\Post(
        path: '/api/import',
        summary: 'Загрузить xlsx для импорта',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Задача создана', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'taskId', type: 'string')]
            )),
            new OA\Response(response: 400, description: 'Ошибка валидации'),
        ]
    )]

    public function upload(Request $request, Response $response): Response
    {
        // 1. Получаем загруженные файлы (должно быть первым)
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        // 2. Проверка наличия файла и ошибки загрузки
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode([
                'error' => 'Файл не загружен',
                'debug' => [
                    'uploaded_keys' => array_keys($uploadedFiles),
                    'error_code' => $file?->getError(),
                    'post_keys' => array_keys($_POST),
                    'files_keys' => array_keys($_FILES),
                ],
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 3. Проверка размера (не более 10 МБ)
        $maxSize = 10 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            $response->getBody()->write(json_encode(['error' => 'Файл слишком большой (макс. 10 МБ)']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 4. Проверка расширения
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $response->getBody()->write(json_encode(['error' => 'Допустим только .xlsx']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 5. Проверка MIME через finfo (строгая, по содержимому)
        $tmpPath = $file->getStream()->getMetadata('uri');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
        ];

        if (!in_array($realMime, $allowedMimes, true)) {
            $response->getBody()->write(json_encode([
                'error' => 'Неверный тип файла. Ожидается .xlsx',
                'detected_mime' => $realMime,
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // 6. Сохранение файла
        $taskId = Uuid::uuid4()->toString();
        $filePath = $this->uploadDir . '/' . $taskId . '.xlsx';

        try {
            $file->moveTo($filePath);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Не удалось сохранить файл',
                'details' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        // 7. Отправка в очередь
        try {
            $this->bus->dispatch(new ImportProductsMessage($filePath, $taskId));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Не удалось отправить задачу в очередь',
                'details' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        // 8. Успешный ответ
        $response->getBody()->write(json_encode(['taskId' => $taskId]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function status(Request $request, Response $response, array $args): Response
    {
        $taskId = $args['taskId'];
        $reportFile = $this->uploadDir . '/report_' . $taskId . '.json';

        if (!file_exists($reportFile)) {
            $response->getBody()->write(json_encode(['status' => 'pending']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $report = json_decode(file_get_contents($reportFile), true);
        $response->getBody()->write(json_encode($report));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function list(Request $request, Response $response): Response
    {
        $reports = [];
        // Ищем все файлы отчётов в папке uploads
        $files = glob($this->uploadDir . '/report_*.json');

        if ($files) {
            foreach ($files as $file) {
                $content = json_decode(file_get_contents($file), true);
                if ($content) {
                    $reports[] = $content;
                }
            }
        }

        // Сортируем по taskId (так как импорты создаются по порядку, UUID не сортируются по времени)
        // Если хотите сортировать по времени, можно использовать filemtime($file)
        // Для простоты оставим как есть или добавим дату в отчёт (см. ниже).

        $response->getBody()->write(json_encode($reports));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
