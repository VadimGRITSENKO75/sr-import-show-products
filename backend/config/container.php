<?php

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;
use App\Service\ImportService;
use App\Controller\ImportController;
use App\Controller\ProductController;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

use function DI\get;
use function DI\autowire;

return function (ContainerBuilder $builder) {
    // Отключаем автовайринг, чтобы контейнер использовал только явные определения
    $builder->useAutowiring(false);

    $builder->addDefinitions([
        'settings' => [
            'doctrine' => [
                'connection' => [
                    'driver' => 'pdo_pgsql',
                    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                    'port' => (int)($_ENV['DB_PORT'] ?? 5432),
                    'dbname' => $_ENV['DB_NAME'] ?? 'postgres',
                    'user' => $_ENV['DB_USER'] ?? 'postgres',
                    'password' => $_ENV['DB_PASSWORD'] ?? '',
                ],
                'paths' => [__DIR__ . '/../src/Domain'],
                'isDevMode' => true,
            ],
            'upload_dir' => __DIR__ . '/../var/uploads',
            'image_dir' => __DIR__ . '/../public/uploads',
        ],

        // EntityManager
        EntityManager::class => function (Psr\Container\ContainerInterface $c) {
            $settings = $c->get('settings')['doctrine'];

            $config = ORMSetup::createAttributeMetadataConfiguration(
                $settings['paths'],
                $settings['isDevMode']
            );

            $connectionParams = [
                'driver'   => 'pdo_pgsql',
                'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port'     => (int)($_ENV['DB_PORT'] ?? 5432),
                'dbname'   => $_ENV['DB_NAME'] ?? 'postgres',
                'user'     => $_ENV['DB_USER'] ?? 'postgres',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset'  => 'UTF8',
            ];

            $connection = DriverManager::getConnection($connectionParams, $config);
            return new EntityManager($connection, $config);
        },

        // HTTP-клиент для скачивания изображений
        Client::class => function () {
            return new Client(['timeout' => 30]);
        },

        // Сервис импорта
        ImportService::class => function (Psr\Container\ContainerInterface $c) {
            $settings = $c->get('settings');
            return new ImportService(
                $c->get(EntityManager::class),
                $c->get(Client::class),
                $settings['upload_dir'],
                $settings['image_dir']
            );
        },

        // Транспорт RabbitMQ
        AmqpTransport::class => function () {
            $connection = new Connection([
                'host' => $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
                'port' => (int)($_ENV['RABBITMQ_PORT'] ?? 5672),
                'user' => $_ENV['RABBITMQ_USER'] ?? 'guest',
                'password' => $_ENV['RABBITMQ_PASSWORD'] ?? 'guest',
                'vhost' => '/',
                'queue_name' => 'import_products',
            ], [], new PhpSerializer());
            return new AmqpTransport($connection, new PhpSerializer());
        },

        // Шина сообщений (конкретный класс)
        MessageBus::class => function (Psr\Container\ContainerInterface $c) {
            return new MessageBus([
                new SendMessageMiddleware(new SendersLocator([
                    App\Message\ImportProductsMessage::class => [$c->get(AmqpTransport::class)],
                ])),
                new HandleMessageMiddleware(new HandlersLocator([
                    App\Message\ImportProductsMessage::class => [
                        new App\MessageHandler\ImportProductsHandler($c->get(ImportService::class)),
                    ],
                ])),
            ]);
        },

        // Привязка интерфейса к конкретному классу (решает "not instantiable")
        MessageBusInterface::class => get(MessageBus::class),

        // Контроллеры
        ImportController::class => autowire()
            ->constructorParameter('bus', get(MessageBusInterface::class))
            ->constructorParameter('uploadDir', get('settings')['upload_dir']),

        ProductController::class => function (Psr\Container\ContainerInterface $c) {
            return new ProductController($c->get(EntityManager::class));
        },
    ]);
};
