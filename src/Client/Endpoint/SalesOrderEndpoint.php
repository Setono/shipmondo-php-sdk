<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\CreateResponse;
use Setono\Shipmondo\DTO\Collection\PaymentGatewayCollectionResponse;
use Setono\Shipmondo\DTO\Model\PaymentGateway;
use Setono\Shipmondo\DTO\Model\SalesOrder;

final class SalesOrderEndpoint extends Endpoint implements SalesOrderEndpointInterface
{
    public function create(SalesOrder $salesOrder): CreateResponse
    {
        return CreateResponse::fromHttpResponse($this->client->post('sales_orders', $salesOrder));
    }

    public function get(int $page = 1, int $pageSize = 20): PaymentGatewayCollectionResponse
    {
        return $this->mapperBuilder->mapper()
            ->map(
                PaymentGatewayCollectionResponse::class,
                $this->createSource($this->client->get('payment_gateways')),
            )
        ;
    }

    public function getById(int $id): PaymentGateway
    {
        return $this->mapperBuilder->mapper()
            ->map(
                PaymentGateway::class,
                $this->createSource($this->client->get('payment_gateways', ['id' => $id])),
            )
        ;
    }
}
