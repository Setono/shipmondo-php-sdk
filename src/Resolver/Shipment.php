<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

final readonly class Shipment
{
    public function __construct(
        public string $shippingMethod,
        public string $senderCountry,
        public string $receiverCountry,
        public int $weight,
        /**
         * Whether the shipment can be broken down into multiple shipments.
         * If the shipment cannot be broken down more or less randomly, you should manually create multiple shipments instead
         */
        public bool $divisible = false,
    ) {
    }
}
