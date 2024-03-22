<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\PaymentGateways\PaymentGateway;

/**
 * @extends EndpointInterface<PaymentGateway>
 */
interface PaymentGatewaysEndpointInterface extends EndpointInterface
{
    public function getById(int $id): PaymentGateway;
}
