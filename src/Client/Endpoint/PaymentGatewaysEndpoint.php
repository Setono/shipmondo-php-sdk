<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Response\Collection;
use Setono\Shipmondo\Response\PaymentGateways\PaymentGateway;

/**
 * @extends Endpoint<PaymentGateway>
 */
final class PaymentGatewaysEndpoint extends Endpoint implements PaymentGatewaysEndpointInterface
{
    public function get(CollectionQuery $query = null): Collection
    {
        /** @var class-string<Collection<PaymentGateway>> $class */
        $class = 'Setono\Shipmondo\Response\Collection<Setono\Shipmondo\Response\PaymentGateways\PaymentGateway>';

        return $this->mapperBuilder->mapper()
            ->map(
                $class,
                $this->createSource($this->client->get(
                    'payment_gateways',
                    $query ?? new CollectionQuery(),
                )),
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
