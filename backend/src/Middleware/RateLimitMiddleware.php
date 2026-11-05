<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

final class RateLimitMiddleware implements MiddlewareInterface
{
    /** @var array<string, list<int>> */
    private static array $hits = [];

    public function __construct(
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($request->getMethod() !== 'POST' || $request->getUri()->getPath() !== '/api/import') {
            return $handler->handle($request);
        }

        $key = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $now = time();
        self::$hits[$key] = array_values(array_filter(
            self::$hits[$key] ?? [],
            fn (int $ts) => ($now - $ts) < $this->windowSeconds
        ));

        if (count(self::$hits[$key]) >= $this->maxRequests) {
            $response = new SlimResponse(429);
            $response->getBody()->write(json_encode(['error' => 'Too many import requests'], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json');
        }

        self::$hits[$key][] = $now;

        return $handler->handle($request);
    }
}
