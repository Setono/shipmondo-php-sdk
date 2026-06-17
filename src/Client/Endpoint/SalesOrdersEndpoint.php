<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\SalesOrder\SalesOrder as SalesOrderRequest;
use Setono\Shipmondo\Response\SalesOrder\SalesOrder as SalesOrderResponse;

/**
 * @extends CollectionEndpoint<SalesOrderResponse>
 */
final class SalesOrdersEndpoint extends CollectionEndpoint
{
    /**
     * Fetch a single sales order by id (`GET /sales_orders/{id}`).
     */
    public function getById(int $id): SalesOrderResponse
    {
        return $this->getOne($id);
    }

    /**
     * Create a sales order (`POST /sales_orders`).
     */
    public function create(SalesOrderRequest $request): SalesOrderResponse
    {
        return $this->createOne($request);
    }

    protected static function getPath(): string
    {
        return 'sales_orders';
    }

    /**
     * @return class-string<SalesOrderResponse>
     */
    protected static function getItemClass(): string
    {
        return SalesOrderResponse::class;
    }
}
