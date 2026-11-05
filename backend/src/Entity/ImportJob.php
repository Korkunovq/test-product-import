<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_jobs')]
class ImportJob
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'total_rows', type: 'integer')]
    private int $totalRows = 0;

    #[ORM\Column(name: 'processed_rows', type: 'integer')]
    private int $processedRows = 0;

    #[ORM\Column(name: 'success_count', type: 'integer')]
    private int $successCount = 0;

    #[ORM\Column(name: 'error_count', type: 'integer')]
    private int $errorCount = 0;

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $errors = [];

    #[ORM\Column(name: 'file_path', type: 'string', length: 1024, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function setTotalRows(int $totalRows): self
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    public function setProcessedRows(int $processedRows): self
    {
        $this->processedRows = $processedRows;

        return $this;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function setSuccessCount(int $successCount): self
    {
        $this->successCount = $successCount;

        return $this;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @param array<int, array<string, mixed>> $errors */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function addError(int $row, string $message, ?string $externalCode = null): self
    {
        $this->errors[] = [
            'row' => $row,
            'external_code' => $externalCode,
            'message' => $message,
        ];
        ++$this->errorCount;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $progress = $this->totalRows > 0
            ? round($this->processedRows / $this->totalRows * 100, 1)
            : 0;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_rows' => $this->totalRows,
            'processed_rows' => $this->processedRows,
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'progress_percent' => $progress,
            'errors' => $this->errors,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'finished_at' => $this->finishedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
