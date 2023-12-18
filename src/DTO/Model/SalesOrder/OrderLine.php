<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO\Model\SalesOrder;

final class OrderLine implements \JsonSerializable
{
    public const LINE_TYPE_ITEM = 'item';

    public const LINE_TYPE_SHIPPING = 'shipping';

    public const LINE_TYPE_DISCOUNT = 'discount';

    public const LINE_TYPE_GIFT_CARD = 'gift_card';

    public const LINE_TYPE_PAYMENT_FEE = 'payment_fee';

    public function __construct(
        public ?string $lineType = self::LINE_TYPE_ITEM,
        public ?string $itemName = null,
        public ?string $itemSku = null,
        public ?string $itemVariantCode = null,
        public int|float $quantity = 1,
        public ?string $unitPriceExcludingVat = null,
        public ?string $discountAmountExcludingVat = null,
        public ?string $vatPercent = null,
        public ?string $currencyCode = null,
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

    public function jsonSerialize(): array
    {
        return array_filter([
            'line_type' => $this->lineType,
            'item_name' => $this->itemName,
            'item_sku' => $this->itemSku,
            'item_variant_code' => $this->itemVariantCode,
            'quantity' => $this->quantity,
            'unit_price_excluding_vat' => $this->unitPriceExcludingVat,
            'discount_amount_excluding_vat' => $this->discountAmountExcludingVat,
            'vat_percent' => $this->vatPercent,
            'currency_code' => $this->currencyCode,
            'unit_weight' => $this->unitWeight,
            'item_barcode' => $this->itemBarcode,
            'item_bin' => $this->itemBin,
            'image_url' => $this->imageUrl,
            'cost_price' => $this->costPrice,
            'country_code_of_origin' => $this->countryCodeOfOrigin,
            'customs_commodity_code' => $this->customsCommodityCode,
            'customs_description' => $this->customsDescription,
        ]);
    }
}
