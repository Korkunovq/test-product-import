<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ProductRepository
{
    /** @var EntityRepository<Product> */
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        $this->repository = $em->getRepository(Product::class);
    }

    public function findById(int $id): ?Product
    {
        return $this->repository->find($id);
    }

    public function findByExternalCode(string $externalCode): ?Product
    {
        return $this->repository->findOneBy(['externalCode' => $externalCode]);
    }

    /**
     * @return array{items: list<Product>, total: int, page: int, limit: int, total_pages: int}
     */
    public function findPaginated(
        int $page,
        int $limit,
        ?string $name = null,
        ?float $priceMin = null,
        ?float $priceMax = null,
    ): array {
        $qb = $this->repository->createQueryBuilder('p');

        if ($name !== null && $name !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :name')
                ->setParameter('name', '%' . mb_strtolower($name) . '%');
        }

        if ($priceMin !== null) {
            $qb->andWhere('p.price >= :priceMin')
                ->setParameter('priceMin', (string) $priceMin);
        }

        if ($priceMax !== null) {
            $qb->andWhere('p.price <= :priceMax')
                ->setParameter('priceMax', (string) $priceMax);
        }

        $qb->orderBy('p.id', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, false);
        $total = count($paginator);
        $items = iterator_to_array($paginator->getIterator());

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => (int) max(1, (int) ceil($total / $limit)),
        ];
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
    }

    public function remove(Product $product): void
    {
        $this->em->remove($product);
    }
}
