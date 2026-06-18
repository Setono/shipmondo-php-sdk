<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

/**
 * Sender address payload for a sales order. Uses the `vat_id` field (the `ship_to` / `bill_to`
 * addresses use `vat_no` instead — see {@see Recipient}).
 */
final class Sender extends Payload
{
    public function __construct(
        public readonly string $name,
        public readonly string $address1,
        public readonly string $city,
        public readonly string $zipcode,
        public readonly string $countryCode,
        public readonly ?string $attention = null,
        public readonly ?string $vatId = null,
        public readonly ?string $email = null,
        public readonly ?string $mobile = null,
        public readonly ?string $telephone = null,
        public readonly ?string $address2 = null,
    ) {
        Assert::stringNotEmpty($name);
        Assert::stringNotEmpty($address1);
        Assert::stringNotEmpty($city);
        Assert::stringNotEmpty($zipcode);
        Assert::stringNotEmpty($countryCode);
    }
}
