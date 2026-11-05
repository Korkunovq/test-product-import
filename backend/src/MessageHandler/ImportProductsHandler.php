<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ImportProductsMessage;
use App\Service\ProductImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportProductsHandler
{
    public function __construct(
        private readonly ProductImportService $importService,
    ) {
    }

    public function __invoke(ImportProductsMessage $message): void
    {
        $this->importService->processFile($message->jobId);
    }
}
