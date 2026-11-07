<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\CollectionRequestOptions;
use Setono\Shipmondo\Request\SalesOrder\SalesOrderRequest;
use Setono\Shipmondo\Request\SalesOrderSearch;
use Setono\Shipmondo\Response\Collection\Collection;
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
     * Fetch one page of the sales orders that match the search (`GET /sales_orders?order_id=…`). Shipmondo lists at
     * most 25 sales orders per page, and lists a sales order that was just created only after a short delay (see the
     * README's note on eventual consistency).
     *
     * @return Collection<SalesOrder>
     */
    public function search(SalesOrderSearch $search, ?CollectionRequestOptions $opts = null): Collection
    {
        $opts ??= new CollectionRequestOptions();

        return $this->mapPage($this->client->get(static::getPath(), $search->toArray() + $opts->toArray()));
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
