<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

final class ServicePoint extends Payload
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $address1,
        public readonly string $zipcode,
        public readonly string $city,
        public readonly string $countryCode,
        public readonly ?string $address2 = null,
        public readonly ?string $carrierCode = null,
    ) {
        Assert::stringNotEmpty($id);
        Assert::stringNotEmpty($name);
        Assert::stringNotEmpty($address1);
        Assert::stringNotEmpty($zipcode);
        Assert::stringNotEmpty($city);
        Assert::stringNotEmpty($countryCode);
    }
}
