<?php declare(strict_types=1);

use App\User\Infrastructure\Action\AddUserAction;
use App\User\Infrastructure\Action\ListUsersAction;
use App\User\Infrastructure\Action\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Controller\ProductController;
use App\Controller\ImportController;
use App\Middleware\RateLimitMiddleware;
use Doctrine\ORM\EntityManager;

return static function (App $app) {
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);// Исправлено: ViewUserAction
        $group->post('', AddUserAction::class);
    });

    // --- Товары ---
    $app->get('/api/products', [ProductController::class, 'list']);
    $app->get('/api/products/{id}', [ProductController::class, 'show']);

    // --- Импорт ---
    // 1. Загрузка (только один раз, с Rate Limit)
    $app->post('/api/import', [ImportController::class, 'upload'])
        ->add(new RateLimitMiddleware(5, 60));

    // 2. Список импортов
    $app->get('/api/import/list', [ImportController::class, 'list']);

    // 3. Статус/отчет конкретного импорта (только один раз!)
    $app->get('/api/import/status/{taskId}', [ImportController::class, 'status']);

    // --- Health Check ---
    $app->get('/api/health', function (Request $request, Response $response) {
        $status = ['status' => 'ok', 'timestamp' => date('c')];

        try {
            $em = $this->get(EntityManager::class);
            $em->getConnection()->executeQuery('SELECT 1');
            $status['db'] = 'ok';
        } catch (Exception $e) {
            $status['db'] = 'error';
            $status['db_error'] = $e->getMessage();
            $status['status'] = 'degraded';
            $response = $response->withStatus(503);
        }

        $response->getBody()->write(json_encode($status));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/docs', function (Request $request, Response $response) {
        $file = __DIR__ . '/../public/docs/index.html';
        if (!file_exists($file)) {
            $response->getBody()->write('Swagger UI not found');
            return $response->withStatus(404);
        }
        $html = file_get_contents($file);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/docs/openapi.json', function (Request $request, Response $response) {
        $file = __DIR__ . '/../public/docs/openapi.json';
        if (!file_exists($file)) {
            $response->getBody()->write(json_encode(['error' => 'openapi.json not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $json = file_get_contents($file);
        $response->getBody()->write($json);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
