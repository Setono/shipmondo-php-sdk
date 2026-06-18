<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;

/**
 * Monetary amounts are sent as decimal strings (e.g. "2000.0"), matching the Shipmondo API. All
 * properties are optional and mutable; unset (`null`) fields are omitted from the serialized JSON.
 */
final class PaymentDetails extends Payload
{
    public function __construct(
        public ?string $amountIncludingVat = null,
        public ?string $vatAmount = null,
        public ?string $currencyCode = null,
        public ?string $amountExcludingVat = null,
        public ?string $authorizedAmount = null,
        public ?string $vatPercent = null,
        public ?string $paymentMethod = null,
        public ?string $transactionId = null,
        public ?string $paymentGatewayId = null,
    ) {
    }
}
