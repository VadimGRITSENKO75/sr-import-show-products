<?php declare(strict_types=1);

use App\Common\Infrastructure\Doctrine\MySqlQuoteStrategy;
use App\Controller\ImportController;
use App\Controller\ProductController;
use App\Message\ImportProductsMessage;
use App\MessageHandler\ImportProductsHandler;
use App\Service\ImportService;
use App\User\Infrastructure\Type\UserIdType;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader as DoctrineConfigurationLoader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use GuzzleHttp\Client;
use Kekos\DoctrineConsoleFactory\MigrationsConfigurationLoader;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

use function DI\autowire;
use function DI\get;

return [
    // PSR-7 фабрики
    RequestFactoryInterface::class => autowire(Psr17Factory::class),
    ResponseFactoryInterface::class => autowire(Psr17Factory::class),
    ServerRequestFactoryInterface::class => autowire(Psr17Factory::class),
    StreamFactoryInterface::class => autowire(Psr17Factory::class),
    UploadedFileFactoryInterface::class => autowire(Psr17Factory::class),
    UriFactoryInterface::class => autowire(Psr17Factory::class),

    // Логгер
    LoggerInterface::class => static function (ContainerInterface $c) {
        $settings = $c->get('settings');
        $loggerSettings = $settings['logger'];

        $logger = new Logger($loggerSettings['name']);
        $logger->pushProcessor(new UidProcessor());
        $logger->pushHandler(new StreamHandler($loggerSettings['path'], $loggerSettings['level']));

        return $logger;
    },

    // EntityManager (PostgreSQL, драйвер атрибутов, snake_case для колонок)
    EntityManager::class => static function (ContainerInterface $c) {
        $settings = $c->get('settings')['doctrine'];
        $isDev = (($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development') !== 'production');
        $proxyDir = $isDev ? null : ($settings['proxy_dir'] ?? null);

        Type::addType(UserIdType::USER_ID, UserIdType::class);

        // Драйвер атрибутов (ваши сущности используют #[ORM\Entity] и т.д.)
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__ . '/../src/Domain'],
            $isDev,
            $proxyDir
        );

        // ВАЖНО: snake_case для колонок (externalCode → external_code)
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        $config->setQuoteStrategy(new MySqlQuoteStrategy());

        $connectionParams = [
            'driver'   => 'pdo_pgsql',
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => (int)($_ENV['DB_PORT'] ?? 5432),
            'dbname'   => $_ENV['DB_NAME'] ?? 'postgres',
            'user'     => $_ENV['DB_USER'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset'  => 'UTF8',
        ];

        $conn = DriverManager::getConnection($connectionParams, $config);
        return new EntityManager($conn, $config);
    },

    // Конфигурация миграций
    DoctrineConfigurationLoader::class => static function (ContainerInterface $c) {
        $settings = $c->get('settings')['doctrine']['migrations'];
        return new MigrationsConfigurationLoader($settings);
    },

    // HTTP-клиент для скачивания изображений
    Client::class => static fn () => new Client(['timeout' => 30]),

    // Сервис импорта
    ImportService::class => static function (ContainerInterface $c) {
        $settings = $c->get('settings');
        return new ImportService(
            $c->get(EntityManager::class),
            $c->get(Client::class),
            $settings['upload_dir'],
            $settings['image_dir']
        );
    },

    // Транспорт RabbitMQ
    AmqpTransport::class => static function () {
        $host = $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq';
        $port = $_ENV['RABBITMQ_PORT'] ?? '5672';
        $user = $_ENV['RABBITMQ_USER'] ?? 'guest';
        $pass = $_ENV['RABBITMQ_PASSWORD'] ?? 'guest';
        $queueName = 'import_products';

        $dsn = sprintf(
            'amqp://%s:%s@%s:%s/%%2f/%s',
            $user,
            $pass,
            $host,
            $port,
            $queueName
        );

        $connection = Connection::fromDsn($dsn);

        return new AmqpTransport($connection, new PhpSerializer());
    },

    // Шина сообщений
    MessageBus::class => static function (ContainerInterface $c) {
        $transport = $c->get(AmqpTransport::class);

        $sendersContainer = new class ($transport) implements ContainerInterface {
            public function __construct(
                private AmqpTransport $transport
            ) {
            }

            public function get(string $id): mixed
            {
                return $this->transport;
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        return new MessageBus([
            new SendMessageMiddleware(new SendersLocator(
                [
                    ImportProductsMessage::class => ['amqp'],
                ],
                $sendersContainer
            )),
            new HandleMessageMiddleware(new HandlersLocator([
                ImportProductsMessage::class => [
                    new ImportProductsHandler($c->get(ImportService::class)),
                ],
            ])),
        ]);
    },

    // Привязка интерфейса к реализации
    MessageBusInterface::class => get(MessageBus::class),

    // Контроллеры
    ImportController::class => static function (ContainerInterface $c) {
        return new ImportController(
            $c->get(MessageBusInterface::class),
            $c->get('settings')['upload_dir']
        );
    },

    ProductController::class => static function (ContainerInterface $c) {
        return new ProductController($c->get(EntityManager::class));
    },
];