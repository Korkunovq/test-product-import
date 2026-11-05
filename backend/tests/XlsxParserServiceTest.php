<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\XlsxParserService;
use PHPUnit\Framework\TestCase;

final class XlsxParserServiceTest extends TestCase
{
    public function testCalculateDiscount(): void
    {
        $parser = new XlsxParserService();
        $discount = $parser->calculateDiscount(1320.0, 880.0);

        $this->assertEqualsWithDelta(33.33, $discount, 0.01);
    }

    public function testCalculateDiscountReturnsNullWhenNoPurchasePrice(): void
    {
        $parser = new XlsxParserService();
        $this->assertNull($parser->calculateDiscount(100.0, null));
    }
}
