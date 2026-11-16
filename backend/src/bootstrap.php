<?php declare(strict_types=1);

use Dotenv\Dotenv;
use App\Common\Application\ContainerFactory;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (empty($_SERVER['APP_ENV']) && empty($_ENV['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new RuntimeException('...');
    }
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

// Если APP_ENV не задан ни в $_SERVER, ни в $_ENV, устанавливаем значение по умолчанию
if (empty($_SERVER['APP_ENV']) && empty($_ENV['APP_ENV'])) {
    $_SERVER['APP_ENV'] = 'development';
    $_ENV['APP_ENV'] = 'development';
}

$container = ContainerFactory::create();

date_default_timezone_set($container->get('settings')['default_timezone']);

AppFactory::setContainer($container);
$app = AppFactory::create();
