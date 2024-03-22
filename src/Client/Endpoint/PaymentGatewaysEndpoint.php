<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\PaymentGateways\PaymentGateway;

/**
 * @extends Endpoint<PaymentGateway>
 */
final class PaymentGatewaysEndpoint extends Endpoint implements PaymentGatewaysEndpointInterface
{
    public function getById(int $id): PaymentGateway
    {
        return $this->mapperBuilder->mapper()
            ->map(
                self::getResponseClass(),
                $this->createSource($this->client->get($this->endpoint, ['id' => $id])),
            )
        ;
    }

    protected static function getResponseClass(): string
    {
        return PaymentGateway::class;
    }
}
