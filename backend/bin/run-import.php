<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$jobId = (int) ($argv[1] ?? 0);
if ($jobId <= 0) {
    fwrite(STDERR, "Usage: run-import.php <jobId>\n");
    exit(1);
}

echo "Starting job {$jobId}\n";

$container = App\Kernel\AppFactory::buildContainer();
$service = $container->get(App\Service\ProductImportService::class);

try {
    $service->processFile($jobId);
    echo "Done\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e::class . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
