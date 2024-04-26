<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\PickupPoints;

use Setono\Shipmondo\Request\Query\Query;
use Webmozart\Assert\Assert;

final class PickupPointsCollectionQuery extends Query
{
    public function __construct(
        string $carrierCode,
        string $countryCode,
        string $zipCode,
        string $address = null,
        string $city = null,
    ) {
        Assert::stringNotEmpty($carrierCode, 'The carrier code must be a non-empty string');
        Assert::stringNotEmpty($countryCode, 'The country code must be a non-empty string');
        Assert::stringNotEmpty($zipCode, 'The zip code must be a non-empty string');
        Assert::nullOrStringNotEmpty($address, 'The address must be a non-empty string');
        Assert::nullOrStringNotEmpty($city, 'The city must be a non-empty string');

        parent::__construct(array_filter([
            'carrier_code' => $carrierCode,
            'country_code' => $countryCode,
            'zipcode' => $zipCode,
            'address' => $address,
            'city' => $city,
        ]));
    }
}
