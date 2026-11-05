<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductImage;
use Doctrine\ORM\EntityManagerInterface;

class ProductImageRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(ProductImage $image): void
    {
        $this->em->persist($image);
    }
}
