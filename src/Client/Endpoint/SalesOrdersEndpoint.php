<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\CreateResponse;
use Setono\Shipmondo\Request\SalesOrders\SalesOrder;

final class SalesOrdersEndpoint extends Endpoint implements SalesOrdersEndpointInterface
{
    public function create(SalesOrder $salesOrder): CreateResponse
    {
        return CreateResponse::fromHttpResponse($this->client->post('sales_orders', $salesOrder));
    }
}
