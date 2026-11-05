<?php

declare(strict_types=1);

use App\Kernel\AppFactory;
use Slim\Factory\AppFactory as SlimAppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$container = AppFactory::buildContainer();
SlimAppFactory::setContainer($container);

$app = SlimAppFactory::create();
AppFactory::registerMiddleware($app);
AppFactory::registerRoutes($app);

$app->run();
