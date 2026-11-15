<?php

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Dotenv\Dotenv;

// Загружаем .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Конфигурация для тестовой БД (создайте базу app_db_test заранее)
$connectionParams = [
    'driver'   => 'pdo_pgsql',
    'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port'     => (int)($_ENV['DB_PORT'] ?? 5432),
    'dbname'   => 'testing', // Отдельная тестовая база!
    'user'     => $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset'  => 'UTF8',
];

$config = ORMSetup::createAttributeMetadataConfiguration(
    [__DIR__ . '/../src/Domain'],
    true // isDevMode
);

// Важно: используем ту же стратегию именования, что и в приложении
$config->setNamingStrategy(new Doctrine\ORM\Mapping\UnderscoreNamingStrategy());

$connection = DriverManager::getConnection($connectionParams, $config);
$entityManager = new EntityManager($connection, $config);

// Создаем/обновляем схему
$tool = new SchemaTool($entityManager);
$classes = $entityManager->getMetadataFactory()->getAllMetadata();
$tool->dropSchema($classes);
$tool->createSchema($classes);

return $entityManager;
