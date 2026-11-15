<?php

return [
    'driver'   => 'pdo_pgsql',
    'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port'     => (int)($_ENV['DB_PORT'] ?? 5432),
    'dbname'   => $_ENV['DB_NAME'] ?? 'postgres', // Укажите вашу БД, например 'postgres' или 'app_db'
    'user'     => $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? 'vadim',
    'charset'  => 'UTF8',
];