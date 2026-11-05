<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HealthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[OA\Get(path: '/api/health', summary: 'Health check', tags: ['System'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function __invoke(Request $request, Response $response): Response
    {
        $dbOk = false;
        try {
            $this->em->getConnection()->executeQuery('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $payload = json_encode([
            'status' => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk ? 'up' : 'down',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($dbOk ? 200 : 503);
    }
}
