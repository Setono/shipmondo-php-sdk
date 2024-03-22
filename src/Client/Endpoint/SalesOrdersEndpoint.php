<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\SingleResourceResponse;
use Setono\Shipmondo\Request\SalesOrders\SalesOrder;

/**
 * @extends Endpoint<SalesOrder>
 */
final class SalesOrdersEndpoint extends Endpoint implements SalesOrdersEndpointInterface
{
    public function create(SalesOrder $salesOrder): SingleResourceResponse
    {
        return SingleResourceResponse::fromHttpResponse($this->client->post('sales_orders', $salesOrder));
    }
}
