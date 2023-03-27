<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\DTO\PaymentGateway;
use Setono\Shipmondo\DTO\PaymentGatewayCollectionResponse;

final class PaymentGatewaysEndpoint extends Endpoint implements PaymentGatewaysEndpointInterface
{
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
