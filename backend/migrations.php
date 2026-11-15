<?php
// backend/migrations.php
return [
    'migrations_paths' => [
        'App\Migrations' => __DIR__ . '/src/Migrations',
    ],
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
];