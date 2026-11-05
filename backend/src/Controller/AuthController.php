<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'JWT token',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'token', type: 'string'),
                ])
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(Request $request, Response $response): Response
    {
        /** @var array{email?: string, password?: string} $body */
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $token = $this->authService->authenticate(
            $body['email'] ?? '',
            $body['password'] ?? ''
        );

        if ($token === null) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials'], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $response->getBody()->write(json_encode(['token' => $token], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
