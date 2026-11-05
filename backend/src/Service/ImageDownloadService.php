<?php

declare(strict_types=1);

namespace App\Service;

class ImageDownloadService
{
    public function __construct(
        private readonly string $storagePath,
        private readonly string $publicBaseUrl,
    ) {
    }

    public function download(string $url, string $externalCode, int $index): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'ProductImport/1.0',
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return null;
        }

        $extension = $this->guessExtension($url, $content);
        $dir = rtrim($this->storagePath, '/\\') . '/images/' . $this->sanitize($externalCode);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $filename = sprintf('%s_%d.%s', $this->sanitize($externalCode), $index, $extension);
        $fullPath = $dir . '/' . $filename;

        if (file_put_contents($fullPath, $content) === false) {
            return null;
        }

        $relative = 'images/' . $this->sanitize($externalCode) . '/' . $filename;

        return rtrim($this->publicBaseUrl, '/') . '/' . $relative;
    }

    private function guessExtension(string $url, string $content): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (is_string($path) && preg_match('/\.(jpe?g|png|gif|webp)$/i', $path, $m)) {
            return strtolower(ltrim($m[1], '.'));
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);

        return match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function sanitize(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $value) ?: 'product';
    }
}
