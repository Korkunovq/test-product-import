<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\XlsxParserService;
use PHPUnit\Framework\TestCase;

final class ImportXlsxIntegrationTest extends TestCase
{
    public function testParseSampleFileWhenPresent(): void
    {
        $path = dirname(__DIR__, 2) . '/data/import-example.xlsx';
        if (!is_file($path)) {
            $this->markTestSkipped('Sample xlsx not found');
        }

        $parser = new XlsxParserService();
        $rows = $parser->parse($path);

        $this->assertNotEmpty($rows);
        $mapped = $parser->mapRow($rows[0]);
        $this->assertNotSame('', $mapped['external_code']);
        $this->assertGreaterThan(0, $mapped['price']);
    }
}
