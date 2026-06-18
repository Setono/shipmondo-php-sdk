<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Enum\OrderLineType;
use Setono\Shipmondo\Request\Payload;

/**
 * A single sales-order line. All properties are optional and mutable, so a line can be constructed
 * and then mutated in place (e.g. by event subscribers) before being appended to
 * {@see SalesOrderRequest::$orderLines}. Unset (`null`) fields are omitted from the serialized JSON.
 */
final class OrderLine extends Payload
{
    public function __construct(
        public ?string $itemName = null,
        public ?string $currencyCode = null,
        public OrderLineType $lineType = OrderLineType::Item,
        public int|float $quantity = 1,
        public ?string $itemSku = null,
        public ?string $itemVariantCode = null,
        public ?string $unitPriceExcludingVat = null,
        public ?string $discountAmountExcludingVat = null,
        public ?string $vatPercent = null,
        public ?int $unitWeight = null,
        public ?string $itemBarcode = null,
        public ?string $itemBin = null,
        public ?string $imageUrl = null,
        public ?string $costPrice = null,
        public ?string $countryCodeOfOrigin = null,
        public ?string $customsCommodityCode = null,
        public ?string $customsDescription = null,
    ) {
    }
}
