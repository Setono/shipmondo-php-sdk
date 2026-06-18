<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;

/**
 * Sender address payload for a sales order. Uses the `vat_id` field (the `ship_to` / `bill_to`
 * addresses use `vat_no` instead — see {@see Recipient}). All properties are optional and mutable;
 * unset (`null`) fields are omitted from the serialized JSON.
 */
final class Sender extends Payload
{
    public function __construct(
        public ?string $name = null,
        public ?string $address1 = null,
        public ?string $city = null,
        public ?string $zipcode = null,
        public ?string $countryCode = null,
        public ?string $attention = null,
        public ?string $vatId = null,
        public ?string $email = null,
        public ?string $mobile = null,
        public ?string $telephone = null,
        public ?string $address2 = null,
    ) {
    }
}
