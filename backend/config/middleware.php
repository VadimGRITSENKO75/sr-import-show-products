<?php declare(strict_types=1);

use App\Common\Infrastructure\Middleware\ExceptionCatcherMiddleware;
use Kekos\ParseRequestBodyMiddleware\ParseRequestBodyMiddleware;
use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

return static function (App $app) {
    $container = $app->getContainer();
    if ($container === null) {
        throw new RuntimeException('Container not configured');
    }

    $app->addMiddleware($container->get(ParseRequestBodyMiddleware::class));
    $app->addMiddleware($container->get(ExceptionCatcherMiddleware::class));

    $app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
        } else {
            $response = $handler->handle($request);
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:4200')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    });
};
