<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;

/**
 * Address payload for `ship_to` / `bill_to` on a sales order. Uses the `vat_no` field (the
 * `sender` address uses `vat_id` instead — see {@see Sender}) and carries a delivery
 * `instruction`. All properties are optional and mutable; unset (`null`) fields are omitted from
 * the serialized JSON.
 */
final class Recipient extends Payload
{
    public function __construct(
        public ?string $name = null,
        public ?string $address1 = null,
        public ?string $city = null,
        public ?string $zipcode = null,
        public ?string $countryCode = null,
        public ?string $attention = null,
        public ?string $email = null,
        public ?string $mobile = null,
        public ?string $telephone = null,
        public ?string $vatNo = null,
        public ?string $instruction = null,
        public ?string $address2 = null,
    ) {
    }
}
