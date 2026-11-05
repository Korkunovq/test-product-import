<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Product Import API',
    description: 'API импорта и просмотра товаров (Slim + Doctrine)'
)]
#[OA\Server(url: 'http://localhost:8080', description: 'Local Docker')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
final class OpenApiSpec
{
}
