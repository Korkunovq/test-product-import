<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ImportJob;
use App\Message\ImportProductsMessage;
use App\Repository\ImportJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ImportUploadService
{
    private const ALLOWED_MIME = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImportJobRepository $importJobRepository,
        private readonly MessageBusInterface $bus,
        private readonly string $storagePath,
        private readonly int $maxUploadSize,
    ) {
    }

    public function handleUpload(UploadedFileInterface $file): ImportJob
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Upload failed');
        }

        $clientName = $file->getClientFilename() ?? '';
        if (!str_ends_with(strtolower($clientName), '.xlsx')) {
            throw new \InvalidArgumentException('Only .xlsx files are allowed');
        }

        $size = $file->getSize() ?? 0;
        if ($size <= 0 || $size > $this->maxUploadSize) {
            throw new \InvalidArgumentException('File size exceeds limit');
        }

        $mime = $file->getClientMediaType() ?? '';
        if ($mime !== '' && !in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \InvalidArgumentException('Invalid MIME type');
        }

        $uploadDir = rtrim($this->storagePath, '/\\') . '/imports';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException('Cannot create upload directory');
        }

        $target = $uploadDir . '/' . uniqid('import_', true) . '.xlsx';
        $file->moveTo($target);

        $job = new ImportJob();
        $job->setFilePath($target);
        $this->importJobRepository->save($job);
        $this->em->flush();

        $this->bus->dispatch(new ImportProductsMessage($job->getId() ?? 0));

        return $job;
    }
}
