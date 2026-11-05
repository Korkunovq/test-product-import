<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ImportJobRepository;
use App\Service\ImportUploadService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ImportController
{
    public function __construct(
        private readonly ImportUploadService $uploadService,
        private readonly ImportJobRepository $importJobRepository,
    ) {
    }

    #[OA\Post(
        path: '/api/import',
        summary: 'Upload xlsx for async import',
        security: [['bearerAuth' => []]],
        tags: ['Import'],
        responses: [
            new OA\Response(
                response: 202,
                description: 'Import job accepted',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'job_id', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string'),
                ])
            ),
            new OA\Response(response: 400, description: 'File missing'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Rate limit exceeded'),
        ]
    )]
    public function upload(Request $request, Response $response): Response
    {
        $files = $request->getUploadedFiles();
        $file = $files['file'] ?? null;

        if ($file === null) {
            $response->getBody()->write(json_encode(['error' => 'file is required'], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $job = $this->uploadService->handleUpload($file);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        $response->getBody()->write(json_encode([
            'job_id' => $job->getId(),
            'status' => $job->getStatus(),
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(202);
    }

    #[OA\Get(
        path: '/api/import/{id}',
        summary: 'Import job status',
        security: [['bearerAuth' => []]],
        tags: ['Import'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Job status'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function status(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $job = $this->importJobRepository->findById($id);

        if ($job === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($job->toArray(), JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
