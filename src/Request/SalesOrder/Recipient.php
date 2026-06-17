<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

/**
 * Address payload for `ship_to` / `bill_to` on a sales order. Uses the `vat_no` field (the
 * `sender` address uses `vat_id` instead — see {@see Sender}) and carries a delivery
 * `instruction`.
 */
final class Recipient extends Payload
{
    public function __construct(
        public readonly string $name,
        public readonly string $address1,
        public readonly string $city,
        public readonly string $zipcode,
        public readonly string $countryCode,
        public readonly ?string $attention = null,
        public readonly ?string $email = null,
        public readonly ?string $mobile = null,
        public readonly ?string $telephone = null,
        public readonly ?string $vatNo = null,
        public readonly ?string $instruction = null,
        public readonly ?string $address2 = null,
    ) {
        Assert::stringNotEmpty($name);
        Assert::stringNotEmpty($address1);
        Assert::stringNotEmpty($city);
        Assert::stringNotEmpty($zipcode);
        Assert::stringNotEmpty($countryCode);
    }
}
