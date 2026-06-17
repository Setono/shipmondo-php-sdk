<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\SalesOrders\SalesOrder as SalesOrderResponse;

/**
 * @extends EndpointInterface<SalesOrderResponse>
 * @extends CreatableEndpointInterface<SalesOrderResponse>
 * @extends ReadableEndpointInterface<SalesOrderResponse>
 */
interface SalesOrdersEndpointInterface extends EndpointInterface, CreatableEndpointInterface, ReadableEndpointInterface
{
}
