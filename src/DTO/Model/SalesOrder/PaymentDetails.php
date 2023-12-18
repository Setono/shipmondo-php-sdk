<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO\Model\SalesOrder;

final class PaymentDetails implements \JsonSerializable
{
    public function __construct(
        public ?string $amountExcludingVat = null,
        public ?string $amountIncludingVat = null,
        public ?string $authorizedAmount = null,
        public ?string $currencyCode = null,
        public ?string $vatAmount = null,
        public ?string $vatPercent = null,
        public ?string $paymentMethod = null,
        public ?string $transactionId = null,
        public ?string $paymentGatewayId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'amount_excluding_vat' => $this->amountExcludingVat,
            'amount_including_vat' => $this->amountIncludingVat,
            'authorized_amount' => $this->authorizedAmount,
            'currency_code' => $this->currencyCode,
            'vat_amount' => $this->vatAmount,
            'vat_percent' => $this->vatPercent,
            'payment_method' => $this->paymentMethod,
            'transaction_id' => $this->transactionId,
            'payment_gateway_id' => $this->paymentGatewayId,
        ]);
    }
}
