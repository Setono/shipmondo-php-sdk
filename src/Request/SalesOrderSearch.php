<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use Webmozart\Assert\Assert;

/**
 * Query value object for listing sales orders by Shipmondo's filters (`GET /sales_orders?…`).
 *
 * Not a {@see Payload}: the filters are sent as a query string. {@see self::toArray()} emits the snake_case query keys
 * Shipmondo documents; `null` values (filters not given) are dropped by `http_build_query` in the client. Shipmondo
 * doesn't document whether a text filter such as `order_id` matches exactly or partly, so compare the returned values
 * (through `Resource::$raw`) when it matters.
 */
final class SalesOrderSearch
{
    public function __construct(
        public readonly ?string $orderId = null,
        public readonly ?string $reference = null,
        public readonly ?string $billToEmail = null,
        public readonly ?string $billToMobile = null,
        public readonly ?string $shipToEmail = null,
        public readonly ?string $shipToMobile = null,
        public readonly ?bool $archived = null,
        public readonly ?\DateTimeInterface $createdAtMin = null,
        public readonly ?\DateTimeInterface $createdAtMax = null,
        public readonly ?\DateTimeInterface $updatedAtMin = null,
        public readonly ?\DateTimeInterface $updatedAtMax = null,
    ) {
        foreach ([$orderId, $reference, $billToEmail, $billToMobile, $shipToEmail, $shipToMobile] as $filter) {
            Assert::nullOrStringNotEmpty($filter, 'A text filter must be a non-empty string, or null to leave it out');
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'reference' => $this->reference,
            'bill_to_email' => $this->billToEmail,
            'bill_to_mobile' => $this->billToMobile,
            'ship_to_email' => $this->shipToEmail,
            'ship_to_mobile' => $this->shipToMobile,
            'archived' => null === $this->archived ? null : ($this->archived ? 'true' : 'false'),
            'created_at_min' => $this->createdAtMin?->format(\DATE_ATOM),
            'created_at_max' => $this->createdAtMax?->format(\DATE_ATOM),
            'updated_at_min' => $this->updatedAtMin?->format(\DATE_ATOM),
            'updated_at_max' => $this->updatedAtMax?->format(\DATE_ATOM),
        ];
    }
}
