<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductAttribute;
use Doctrine\ORM\EntityManagerInterface;

class ProductAttributeRepository
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(ProductAttribute $attribute): void
    {
        $this->em->persist($attribute);
    }
}
