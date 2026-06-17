<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Enum;

enum OrderLineType: string
{
    case Item = 'item';
    case Shipping = 'shipping';
    case Discount = 'discount';
    case GiftCard = 'gift_card';
    case PaymentFee = 'payment_fee';
}
