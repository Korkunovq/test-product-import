<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ProductController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'List products',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'name', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'price_min', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'price_max', in: 'query', schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated product list'),
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 20)));
        $name = isset($params['name']) ? (string) $params['name'] : null;
        $priceMin = isset($params['price_min']) ? (float) $params['price_min'] : null;
        $priceMax = isset($params['price_max']) ? (float) $params['price_max'] : null;

        $result = $this->productRepository->findPaginated($page, $limit, $name, $priceMin, $priceMax);

        $items = array_map(static fn ($p) => $p->toArray(false), $result['items']);

        $payload = json_encode([
            'data' => $items,
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages'],
            ],
        ], JSON_THROW_ON_ERROR);

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Product detail',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product details'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found'], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($product->toArray(true), JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
