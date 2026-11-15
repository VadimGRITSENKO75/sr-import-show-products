<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private string $storageDir;

    public function __construct(
        private int $maxRequests = 5,
        private int $windowSeconds = 60
    ) {
        // Папка для хранения файлов счетчиков
        $this->storageDir = __DIR__ . '/../../var/rate_limit';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        // Хэшируем IP, чтобы использовать его как имя файла (безопасно)
        $filename = $this->storageDir . '/' . md5($ip) . '.json';
        $now = time();

        // Читаем текущие записи из файла
        $hits = [];
        if (file_exists($filename)) {
            $hits = json_decode(file_get_contents($filename), true) ?: [];
        }

        // Очищаем старые записи
        $hits = array_filter($hits, fn($t) => $t > $now - $this->windowSeconds);

        // Проверяем лимит
        if (count($hits) >= $this->maxRequests) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Too many requests',
                'retry_after' => $this->windowSeconds
            ]));
            return $response->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)$this->windowSeconds);
        }

        // Добавляем текущий запрос и сохраняем
        $hits[] = $now;
        file_put_contents($filename, json_encode($hits));

        return $handler->handle($request);
    }
}