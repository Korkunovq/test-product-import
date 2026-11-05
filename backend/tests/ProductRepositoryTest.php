<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use App\Repository\ProductRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class ProductRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProductRepository $repository;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $this->em = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->repository = new ProductRepository($this->em);
    }

    public function testCreateProduct(): void
    {
        $product = $this->makeProduct('create-001', 'Created Product', '150.00');
        $this->repository->save($product);
        $this->em->flush();

        $this->assertNotNull($product->getId());
        $found = $this->repository->findById((int) $product->getId());
        $this->assertNotNull($found);
        $this->assertSame('Created Product', $found->getName());
        $this->assertSame('create-001', $found->getExternalCode());
    }

    public function testReadProductWithAttributesAndImages(): void
    {
        $product = $this->makeProduct('read-001', 'Read Product', '200.00');

        $attr = new ProductAttribute();
        $attr->setKey('Color');
        $attr->setValue('Red');
        $product->addAttribute($attr);

        $image = new ProductImage();
        $image->setUrl('https://example.com/a.jpg');
        $image->setPath('/storage/images/a.jpg');
        $product->addImage($image);

        $this->repository->save($product);
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findByExternalCode('read-001');
        $this->assertNotNull($found);
        $this->assertCount(1, $found->getAttributes());
        $this->assertSame('Color', $found->getAttributes()->first()->getKey());
        $this->assertCount(1, $found->getImages());
        $this->assertSame('https://example.com/a.jpg', $found->getImages()->first()->getUrl());
    }

    public function testUpdateProduct(): void
    {
        $product = $this->makeProduct('upd-001', 'Old Name', '100.00');
        $this->repository->save($product);
        $this->em->flush();

        $product->setName('Updated Name');
        $product->setPrice('250.50');
        $product->setDiscount('15.00');
        $this->em->flush();
        $this->em->clear();

        $found = $this->repository->findByExternalCode('upd-001');
        $this->assertNotNull($found);
        $this->assertSame('Updated Name', $found->getName());
        $this->assertEqualsWithDelta(250.5, (float) $found->getPrice(), 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $found->getDiscount(), 0.001);
    }

    public function testDeleteProduct(): void
    {
        $product = $this->makeProduct('del-001', 'To Delete', '99.00');
        $this->repository->save($product);
        $this->em->flush();
        $id = (int) $product->getId();

        $this->repository->remove($product);
        $this->em->flush();

        $this->assertNull($this->repository->findById($id));
        $this->assertNull($this->repository->findByExternalCode('del-001'));
    }

    public function testPaginatedListAndFilters(): void
    {
        $this->repository->save($this->makeProduct('p-1', 'Alpha Shirt', '100.00'));
        $this->repository->save($this->makeProduct('p-2', 'Beta Pants', '200.00'));
        $this->repository->save($this->makeProduct('p-3', 'Alpha Jacket', '300.00'));
        $this->em->flush();

        $page = $this->repository->findPaginated(1, 2);
        $this->assertSame(3, $page['total']);
        $this->assertCount(2, $page['items']);
        $this->assertSame(2, $page['total_pages']);

        $byName = $this->repository->findPaginated(1, 20, 'alpha');
        $this->assertSame(2, $byName['total']);

        $byPrice = $this->repository->findPaginated(1, 20, null, 150.0, 250.0);
        $this->assertSame(1, $byPrice['total']);
        $this->assertSame('Beta Pants', $byPrice['items'][0]->getName());
    }

    private function makeProduct(string $code, string $name, string $price): Product
    {
        $product = new Product();
        $product->setExternalCode($code);
        $product->setName($name);
        $product->setDescription('Fixture description');
        $product->setPrice($price);

        return $product;
    }
}
