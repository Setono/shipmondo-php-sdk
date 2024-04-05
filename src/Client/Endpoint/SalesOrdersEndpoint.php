<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\SalesOrders\SalesOrder as SalesOrderResponse;

/**
 * @extends Endpoint<SalesOrderResponse>
 */
final class SalesOrdersEndpoint extends Endpoint implements SalesOrdersEndpointInterface
{
    /**
     * @use CreatableEndpointTrait<SalesOrderResponse>
     */
    use CreatableEndpointTrait;

    protected static function getResponseClass(): string
    {
        return SalesOrderResponse::class;
    }
}
