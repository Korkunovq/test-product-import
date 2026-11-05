<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DataFixtures
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function load(): void
    {
        if ($this->userRepository->findByEmail('admin@example.com') === null) {
            $user = new User();
            $user->setEmail('admin@example.com');
            $user->setPasswordHash(password_hash('admin123', PASSWORD_BCRYPT));
            $this->userRepository->save($user);
        }

        if ($this->productRepository->findByExternalCode('fixture-001') === null) {
            $product = new Product();
            $product->setExternalCode('fixture-001');
            $product->setName('Fixture Product');
            $product->setDescription('Sample product for tests');
            $product->setPrice('999.00');
            $product->setDiscount('10.00');

            $attr = new ProductAttribute();
            $attr->setKey('Color');
            $attr->setValue('Blue');
            $product->addAttribute($attr);

            $image = new ProductImage();
            $image->setUrl('https://example.com/image.jpg');
            $image->setPath('/storage/images/fixture.jpg');
            $product->addImage($image);

            $this->productRepository->save($product);
        }

        $this->em->flush();
    }
}
