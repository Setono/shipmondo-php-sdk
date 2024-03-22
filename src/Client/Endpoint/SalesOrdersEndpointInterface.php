<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\SingleResourceResponse;
use Setono\Shipmondo\Request\SalesOrders\SalesOrder;

/**
 * @extends EndpointInterface<SalesOrder>
 */
interface SalesOrdersEndpointInterface extends EndpointInterface
{
    public function create(SalesOrder $salesOrder): SingleResourceResponse;
}
