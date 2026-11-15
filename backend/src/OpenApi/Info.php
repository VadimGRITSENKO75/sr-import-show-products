<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Import/Export Products API',
    description: 'API для импорта и управления товарами'
)]
#[OA\Server(url: 'http://localhost:8080', description: 'Local dev server')]
class Info
{
}
