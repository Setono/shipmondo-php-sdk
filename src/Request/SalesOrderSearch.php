<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use Webmozart\Assert\Assert;

/**
 * Query value object for listing sales orders by Shipmondo's filters (`GET /sales_orders?…`).
 *
 * Not a {@see Payload}: the filters are sent as a query string. {@see self::toArray()} emits the snake_case query keys
 * Shipmondo documents, and the times in its documented format, with milliseconds; `null` values (filters not given) are
 * dropped by `http_build_query` in the client. Shipmondo doesn't document whether a text filter such as `order_id`
 * matches exactly or partly, so compare the returned values (through `Resource::$raw`) when it matters.
 */
final class SalesOrderSearch
{
    private const TIME_FORMAT = \DateTimeInterface::RFC3339_EXTENDED;

    public function __construct(
        public readonly ?int $id = null,
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
        Assert::nullOrGreaterThan($id, 0, 'The id must be a positive integer');
        Assert::nullOrStringNotEmpty($orderId, 'The order id must be a non-empty string');
        Assert::nullOrStringNotEmpty($reference, 'The reference must be a non-empty string');
        Assert::nullOrStringNotEmpty($billToEmail, 'The bill-to email must be a non-empty string');
        Assert::nullOrStringNotEmpty($billToMobile, 'The bill-to mobile must be a non-empty string');
        Assert::nullOrStringNotEmpty($shipToEmail, 'The ship-to email must be a non-empty string');
        Assert::nullOrStringNotEmpty($shipToMobile, 'The ship-to mobile must be a non-empty string');
        self::assertRange($createdAtMin, $createdAtMax, 'created');
        self::assertRange($updatedAtMin, $updatedAtMax, 'updated');
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'reference' => $this->reference,
            'bill_to_email' => $this->billToEmail,
            'bill_to_mobile' => $this->billToMobile,
            'ship_to_email' => $this->shipToEmail,
            'ship_to_mobile' => $this->shipToMobile,
            'archived' => null === $this->archived ? null : ($this->archived ? 'true' : 'false'),
            'created_at_min' => $this->createdAtMin?->format(self::TIME_FORMAT),
            'created_at_max' => $this->createdAtMax?->format(self::TIME_FORMAT),
            'updated_at_min' => $this->updatedAtMin?->format(self::TIME_FORMAT),
            'updated_at_max' => $this->updatedAtMax?->format(self::TIME_FORMAT),
        ];
    }

    private static function assertRange(?\DateTimeInterface $min, ?\DateTimeInterface $max, string $time): void
    {
        if (null !== $min && null !== $max) {
            Assert::lessThanEq($min, $max, sprintf('The earliest %1$s time must not be after the latest %1$s time', $time));
        }
    }
}
