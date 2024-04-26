<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\PickupPoints;

use Setono\Shipmondo\Request\Query\Query;

final class PickupPointsCollectionQuery extends Query
{
    public function __construct(
        string $carrierCode,
        string $countryCode,
        string $zipCode,
        string $address = null,
        string $city = null,
    ) {
        parent::__construct(array_filter([
            'carrier_code' => $carrierCode,
            'country_code' => $countryCode,
            'zipcode' => $zipCode,
            'address' => $address,
            'city' => $city,
        ]));
    }
}
