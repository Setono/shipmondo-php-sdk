<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

/**
 * Monetary amounts are sent as decimal strings (e.g. "2000.0"), matching the Shipmondo API.
 */
final class PaymentDetails extends Payload
{
    public function __construct(
        public readonly string $amountIncludingVat,
        public readonly string $vatAmount,
        public readonly string $currencyCode,
        public readonly ?string $amountExcludingVat = null,
        public readonly ?string $authorizedAmount = null,
        public readonly ?string $vatPercent = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $paymentGatewayId = null,
    ) {
        Assert::stringNotEmpty($amountIncludingVat);
        Assert::stringNotEmpty($vatAmount);
        Assert::stringNotEmpty($currencyCode);
    }
}
