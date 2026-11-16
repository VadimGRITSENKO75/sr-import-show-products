#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Common\Application\ContainerFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Log\NullLogger;
use Dotenv\Dotenv;

// 1. Загружаем .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// 2. Создаём контейнер
$container = ContainerFactory::create();

// 3. Получаем сервисы
/** @var MessageBusInterface $bus */
$bus = $container->get(MessageBusInterface::class);
/** @var AmqpTransport $transport */
$transport = $container->get(AmqpTransport::class);

// 4. Создаём Worker с полным набором аргументов
$worker = new Worker(
    [$transport],           // receivers
    $bus,                    // bus
    new EventDispatcher(),   // event dispatcher
    new NullLogger()         // logger (можно заменить на ваш LoggerInterface из контейнера)
);

// 5. Запускаем
$worker->run();