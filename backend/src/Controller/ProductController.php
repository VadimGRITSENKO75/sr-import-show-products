<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\Domain\Product;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
    public function __construct(
        private EntityManager $em
    ) {
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'Список товаров с пагинацией и фильтрацией',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Номер страницы (начиная с 1)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Количество товаров на странице (макс. 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'name',
                in: 'query',
                description: 'Поиск по названию (частичное совпадение)',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'price_min',
                in: 'query',
                description: 'Минимальная цена',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float')
            ),
            new OA\Parameter(
                name: 'price_max',
                in: 'query',
                description: 'Максимальная цена',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Постраничный список товаров',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'externalCode', type: 'string', example: 'SKU-001'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Тестовый товар'),
                                    new OA\Property(property: 'price', type: 'string', example: '1000.00'),
                                    new OA\Property(property: 'discount', type: 'string', example: '15.00'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 42),
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'limit', type: 'integer', example: 20),
                    ]
                )
            ),
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(100, max(1, (int)($params['limit'] ?? 20)));
        $name = $params['name'] ?? null;
        $priceMin = $params['price_min'] ?? null;
        $priceMax = $params['price_max'] ?? null;

        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('p');

        if ($name) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:name)')->setParameter('name', "%{$name}%");
        }
        if ($priceMin !== null) {
            $qb->andWhere('p.price >= :min')->setParameter('min', $priceMin);
        }
        if ($priceMax !== null) {
            $qb->andWhere('p.price <= :max')->setParameter('max', $priceMax);
        }

        $total = (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        $products = $qb->getQuery()->getResult();

        $data = array_map(fn (Product $p) => [
            'id' => $p->getId(),
            'externalCode' => $p->getExternalCode(),
            'name' => $p->getName(),
            'price' => $p->getPrice(),
            'discount' => $p->getDiscount(),
        ], $products);

        $response->getBody()->write(json_encode([
            'items' => $data,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Карточка товара с атрибутами и изображениями',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID товара',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Полная информация о товаре',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'externalCode', type: 'string', example: 'SKU-001'),
                        new OA\Property(property: 'name', type: 'string', example: 'Тестовый товар'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Описание'),
                        new OA\Property(property: 'price', type: 'string', example: '1000.00'),
                        new OA\Property(property: 'discount', type: 'string', example: '15.00'),
                        new OA\Property(
                            property: 'attributes',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'key', type: 'string', example: 'Доп. поле: Цвет'),
                                    new OA\Property(property: 'value', type: 'string', example: 'Красный'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'url', type: 'string', example: 'https://example.com/img.jpg'),
                                    new OA\Property(property: 'path', type: 'string', example: '/uploads/img_123.jpg'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Товар не найден',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'error', type: 'string', example: 'Product not found')]
                )
            ),
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        $product = $this->em->getRepository(Product::class)->find((int)$args['id']);

        if (!$product) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'id' => $product->getId(),
            'externalCode' => $product->getExternalCode(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'discount' => $product->getDiscount(),
            'attributes' => array_map(fn ($a) => ['key' => $a->getKey(), 'value' => $a->getValue()], $product->getAttributes()->toArray()),
            'images' => array_map(fn ($i) => ['url' => $i->getUrl(), 'path' => $i->getPath()], $product->getImages()->toArray()),
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
