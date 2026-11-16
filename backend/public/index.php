<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// 1. Загрузка .env ДО bootstrap (чтобы $_ENV были доступны в контейнере)
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// 2. Bootstrap приложения (создаёт $app и контейнер)
require __DIR__ . '/../src/bootstrap.php';

// 3. Создание Request
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// 4. Middleware из конфига (парсинг тела, exception catcher и т.д.)
$middleware = require __DIR__ . '/../config/middleware.php';
$middleware($app);

// 5. Роуты
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// 6. Routing middleware (должен быть добавлен ДО Error, чтобы ошибки маршрутизации перехватывались)
$app->addRoutingMiddleware();

// 7. Error middleware (обрабатывает исключения из роутов и других middleware)
$app->addErrorMiddleware(true, true, true);

// 8. CORS middleware — добавляем ПОСЛЕ Error, чтобы он оборачивал ответы с ошибками
//    (в Slim порядок выполнения обратный порядку добавления: LIFO)
$app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new Slim\Psr7\Response();
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:4200')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// 9. Запуск приложения и отправка ответа
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
