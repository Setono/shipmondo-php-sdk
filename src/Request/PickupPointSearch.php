<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use Webmozart\Assert\Assert;

/**
 * Query value object for searching pickup points (`GET /pickup_points`).
 *
 * Not a {@see Payload}: pickup-point search parameters are sent as a query string, not a JSON
 * body. {@see self::toArray()} emits the snake_case query keys Shipmondo expects (including the
 * misspelled `zipcode`); `null` optional values are dropped by `http_build_query` in the client.
 */
final class PickupPointSearch
{
    public function __construct(
        public readonly string $carrierCode,
        public readonly string $countryCode,
        public readonly string $zipCode,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
    ) {
        Assert::stringNotEmpty($carrierCode, 'The carrier code must be a non-empty string');
        Assert::stringNotEmpty($countryCode, 'The country code must be a non-empty string');
        Assert::stringNotEmpty($zipCode, 'The zip code must be a non-empty string');
        Assert::nullOrStringNotEmpty($address, 'The address must be a non-empty string');
        Assert::nullOrStringNotEmpty($city, 'The city must be a non-empty string');
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'carrier_code' => $this->carrierCode,
            'country_code' => $this->countryCode,
            'zipcode' => $this->zipCode,
            'address' => $this->address,
            'city' => $this->city,
        ];
    }
}
