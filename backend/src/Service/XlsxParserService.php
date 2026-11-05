<?php

declare(strict_types=1);

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxParserService
{
    private const EXTRA_FIELD_PREFIX = 'Доп. поле:';
    private const PHOTO_FIELD = 'Доп. поле: Ссылки на фото';

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            return [];
        }

        $headers = array_map(
            static fn ($h) => trim((string) $h),
            $rows[0]
        );

        $result = [];
        for ($i = 1, $count = count($rows); $i < $count; ++$i) {
            $row = $rows[$i];
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $colIndex => $header) {
                $assoc[$header] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
            }
            $assoc['_row_number'] = $i + 1;
            $result[] = $assoc;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{external_code: string, name: string, description: ?string, price: float, purchase_price: ?float, attributes: list<array{key: string, value: string}>, image_urls: list<string>}
     */
    public function mapRow(array $row): array
    {
        $attributes = [];
        $imageUrls = [];

        foreach ($row as $key => $value) {
            if ($value === '') {
                continue;
            }

            if ($key === self::PHOTO_FIELD) {
                $imageUrls = $this->splitUrls((string) $value);
                continue;
            }

            if (str_starts_with($key, self::EXTRA_FIELD_PREFIX)) {
                $attrKey = trim(substr($key, strlen(self::EXTRA_FIELD_PREFIX)));
                if ($attrKey !== '' && $attrKey !== 'Ссылки на фото') {
                    $attributes[] = ['key' => $attrKey, 'value' => (string) $value];
                }
            }
        }

        return [
            'external_code' => (string) ($row['Внешний код'] ?? ''),
            'name' => (string) ($row['Наименование'] ?? ''),
            'description' => ($row['Описание'] ?? '') !== '' ? (string) $row['Описание'] : null,
            'price' => $this->parseDecimal((string) ($row['Цена: Цена продажи'] ?? '0')),
            'purchase_price' => isset($row['Закупочная цена']) && $row['Закупочная цена'] !== ''
                ? $this->parseDecimal((string) $row['Закупочная цена'])
                : null,
            'attributes' => $attributes,
            'image_urls' => $imageUrls,
        ];
    }

    public function calculateDiscount(float $salePrice, ?float $purchasePrice): ?float
    {
        if ($purchasePrice === null || $salePrice <= 0) {
            return null;
        }

        return round(($salePrice - $purchasePrice) / $salePrice * 100, 2);
    }

    private function parseDecimal(string $value): float
    {
        $normalized = str_replace([' ', "\xc2\xa0"], '', $value);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }

    /** @return list<string> */
    private function splitUrls(string $value): array
    {
        $parts = preg_split('/[,;\s]+/', $value) ?: [];
        $urls = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_URL)) {
                $urls[] = $part;
            }
        }

        return $urls;
    }

    /** @param list<mixed> $row */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
