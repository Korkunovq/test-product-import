<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;
use Psr\Log\NullLogger;

$openapi = Generator::scan(
    [
        __DIR__ . '/../src/OpenApi',
        __DIR__ . '/../src/Controller',
    ],
    ['logger' => new NullLogger()]
);

echo $openapi->toJson();
