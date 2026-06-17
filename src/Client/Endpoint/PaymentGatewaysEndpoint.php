<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\PaymentGateway\PaymentGateway;

/**
 * @extends CollectionEndpoint<PaymentGateway>
 */
final class PaymentGatewaysEndpoint extends CollectionEndpoint
{
    /**
     * Look a payment gateway up by id. Shipmondo exposes this as a `?id=` filter on the list
     * endpoint rather than a REST sub-path.
     */
    public function getById(int $id): PaymentGateway
    {
        return $this->getByQueryId($id);
    }

    protected static function getPath(): string
    {
        return 'payment_gateways';
    }

    /**
     * @return class-string<PaymentGateway>
     */
    protected static function getItemClass(): string
    {
        return PaymentGateway::class;
    }
}
