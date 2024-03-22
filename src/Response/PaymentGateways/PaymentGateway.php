<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\PaymentGateways;

final class PaymentGateway
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $provider,
        public readonly string $merchantNumber,
    ) {
    }
}
