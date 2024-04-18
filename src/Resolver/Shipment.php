<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

final class Shipment
{
    public function __construct(
        public readonly string $shippingMethod,
        public readonly string $senderCountry,
        public readonly string $receiverCountry,
        public readonly int $weight,
        /**
         * Whether the shipment can be broken down into multiple shipments.
         * If the shipment cannot be broken down more or less randomly, you should manually create multiple shipments instead
         */
        public readonly bool $divisible = false,
    ) {
    }
}
