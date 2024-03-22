<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\SalesOrders\SalesOrder;
use Setono\Shipmondo\Response\SingleResourceResponse;

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
