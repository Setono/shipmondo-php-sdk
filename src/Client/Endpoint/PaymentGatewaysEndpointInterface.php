<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\DTO\Collection\PaymentGatewayCollectionResponse;
use Setono\Shipmondo\DTO\Model\PaymentGateway;

interface PaymentGatewaysEndpointInterface extends EndpointInterface
{
    /**
     * @param int<1, max> $page
     * @param int<1, 50> $pageSize
     */
    public function get(int $page = 1, int $pageSize = 20): PaymentGatewayCollectionResponse;

    public function getById(int $id): PaymentGateway;
}
