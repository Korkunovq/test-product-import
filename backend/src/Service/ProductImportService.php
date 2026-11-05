<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ImportJob;
use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use App\Repository\ImportJobRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductImportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $productRepository,
        private readonly ImportJobRepository $importJobRepository,
        private readonly XlsxParserService $parser,
        private readonly ImageDownloadService $imageDownloader,
    ) {
    }

    public function processFile(int $jobId): void
    {
        $job = $this->importJobRepository->findById($jobId);
        if ($job === null || $job->getFilePath() === null) {
            return;
        }

        $job->setStatus(ImportJob::STATUS_PROCESSING);
        $this->em->flush();

        try {
            $rows = $this->parser->parse($job->getFilePath());
            $job->setTotalRows(count($rows));
            $this->em->flush();

            $processed = 0;
            $success = 0;

            foreach ($rows as $row) {
                $rowNumber = (int) ($row['_row_number'] ?? 0);
                try {
                    $mapped = $this->parser->mapRow($row);
                    $this->validateMappedRow($mapped, $rowNumber);
                    $this->upsertProduct($mapped);
                    ++$success;
                } catch (\Throwable $e) {
                    $externalCode = is_string($row['Внешний код'] ?? null) ? $row['Внешний код'] : null;
                    $job->addError($rowNumber, $e->getMessage(), $externalCode);
                }

                ++$processed;
                $job->setProcessedRows($processed);
                $job->setSuccessCount($success);

                if ($processed % 5 === 0) {
                    $this->em->flush();
                }
            }

            $job->setStatus(ImportJob::STATUS_COMPLETED);
            $job->setFinishedAt(new \DateTimeImmutable());
            $this->em->flush();
        } catch (\Throwable $e) {
            $job->addError(0, 'Import failed: ' . $e->getMessage());
            $job->setStatus(ImportJob::STATUS_FAILED);
            $job->setFinishedAt(new \DateTimeImmutable());
            $this->em->flush();

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $mapped
     */
    private function validateMappedRow(array $mapped, int $rowNumber): void
    {
        if ($mapped['external_code'] === '') {
            throw new \InvalidArgumentException(sprintf('Row %d: external_code is required', $rowNumber));
        }

        if ($mapped['name'] === '') {
            throw new \InvalidArgumentException(sprintf('Row %d: name is required', $rowNumber));
        }

        if ((float) $mapped['price'] <= 0) {
            throw new \InvalidArgumentException(sprintf('Row %d: invalid price', $rowNumber));
        }
    }

    /**
     * @param array{external_code: string, name: string, description: ?string, price: float, purchase_price: ?float, attributes: list<array{key: string, value: string}>, image_urls: list<string>} $mapped
     */
    private function upsertProduct(array $mapped): void
    {
        $this->em->wrapInTransaction(function () use ($mapped): void {
            $product = $this->productRepository->findByExternalCode($mapped['external_code']);
            $isNew = $product === null;

            if ($isNew) {
                $product = new Product();
                $product->setExternalCode($mapped['external_code']);
            }

            $product->setName($mapped['name']);
            $product->setDescription($mapped['description']);
            $product->setPrice(number_format($mapped['price'], 2, '.', ''));

            $discount = $this->parser->calculateDiscount($mapped['price'], $mapped['purchase_price']);
            $product->setDiscount($discount !== null ? number_format($discount, 2, '.', '') : null);

            if (!$isNew) {
                $product->clearAttributes();
                $product->clearImages();
            }

            foreach ($mapped['attributes'] as $attr) {
                $attribute = new ProductAttribute();
                $attribute->setKey($attr['key']);
                $attribute->setValue($attr['value']);
                $product->addAttribute($attribute);
            }

            foreach ($mapped['image_urls'] as $index => $url) {
                $image = new ProductImage();
                $image->setUrl($url);
                $localPath = $this->imageDownloader->download($url, $mapped['external_code'], $index);
                $image->setPath($localPath);
                $product->addImage($image);
            }

            $this->productRepository->save($product);
            $this->em->flush();
        });
    }
}
