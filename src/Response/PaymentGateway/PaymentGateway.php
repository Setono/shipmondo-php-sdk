<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\PaymentGateway;

use Setono\Shipmondo\Response\Resource;

final class PaymentGateway extends Resource
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $provider,
        public readonly string $merchantNumber,
    ) {
    }
}
