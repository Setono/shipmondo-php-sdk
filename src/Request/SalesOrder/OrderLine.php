<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Enum\OrderLineType;
use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

final class OrderLine extends Payload
{
    public function __construct(
        public readonly string $itemName,
        public readonly string $currencyCode,
        public readonly OrderLineType $lineType = OrderLineType::Item,
        public readonly int|float $quantity = 1,
        public readonly ?string $itemSku = null,
        public readonly ?string $itemVariantCode = null,
        public readonly ?string $unitPriceExcludingVat = null,
        public readonly ?string $discountAmountExcludingVat = null,
        public readonly ?string $vatPercent = null,
        public readonly ?int $unitWeight = null,
        public readonly ?string $itemBarcode = null,
        public readonly ?string $itemBin = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $costPrice = null,
        public readonly ?string $countryCodeOfOrigin = null,
        public readonly ?string $customsCommodityCode = null,
        public readonly ?string $customsDescription = null,
    ) {
        Assert::stringNotEmpty($itemName);
        Assert::stringNotEmpty($currencyCode);
    }
}
