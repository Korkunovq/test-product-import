<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

final class JwtAuthMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private array $publicPaths = [
        '/api/health',
        '/api/auth/login',
        '/api/openapi.json',
        '/api/docs',
    ];

    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        foreach ($this->publicPaths as $public) {
            if ($path === $public || str_starts_with($path, '/storage/')) {
                return $handler->handle($request);
            }
        }

        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = substr($header, 7);
        $payload = $this->authService->validateToken($token);
        if ($payload === null) {
            return $this->unauthorized();
        }

        return $handler->handle($request->withAttribute('user', $payload));
    }

    private function unauthorized(): Response
    {
        $response = new SlimResponse(401);
        $response->getBody()->write(json_encode(['error' => 'Unauthorized'], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
