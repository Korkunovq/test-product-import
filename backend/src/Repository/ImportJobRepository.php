<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImportJob;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ImportJobRepository
{
    /** @var EntityRepository<ImportJob> */
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        $this->repository = $em->getRepository(ImportJob::class);
    }

    public function findById(int $id): ?ImportJob
    {
        return $this->repository->find($id);
    }

    public function save(ImportJob $job): void
    {
        $this->em->persist($job);
    }
}
