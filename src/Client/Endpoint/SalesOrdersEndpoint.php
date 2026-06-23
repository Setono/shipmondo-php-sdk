<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\SalesOrder\SalesOrderRequest;
use Setono\Shipmondo\Response\SalesOrder\SalesOrder;

/**
 * @extends CollectionEndpoint<SalesOrder>
 */
final class SalesOrdersEndpoint extends CollectionEndpoint
{
    /**
     * Fetch a single sales order by id (`GET /sales_orders/{id}`).
     */
    public function getById(int $id): SalesOrder
    {
        return $this->getOne($id);
    }

    /**
     * Create a sales order (`POST /sales_orders`).
     */
    public function create(SalesOrderRequest $request): SalesOrder
    {
        return $this->createOne($request);
    }

    /**
     * Delete a sales order by id (`DELETE /sales_orders/{id}`).
     */
    public function delete(int $id): void
    {
        $this->client->delete('sales_orders', $id);
    }

    protected static function getPath(): string
    {
        return 'sales_orders';
    }

    /**
     * @return class-string<SalesOrder>
     */
    protected static function getItemClass(): string
    {
        return SalesOrder::class;
    }
}
