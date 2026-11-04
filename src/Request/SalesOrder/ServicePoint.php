<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Request\Payload;
use Setono\Shipmondo\Response\PickupPoint\PickupPoint;

/**
 * Service point (pickup point) attached to a sales order. All properties are optional and mutable;
 * unset (`null`) fields are omitted from the serialized JSON.
 */
final class ServicePoint extends Payload
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $address1 = null,
        public ?string $zipcode = null,
        public ?string $city = null,
        public ?string $countryCode = null,
        public ?string $address2 = null,
        public ?string $carrierCode = null,
    ) {
    }

    /**
     * The service point of a sales order delivered to a pickup point, e.g. one found with
     * {@see \Setono\Shipmondo\Client\Endpoint\PickupPointsEndpoint::search()}
     */
    public static function fromPickupPoint(PickupPoint $pickupPoint): self
    {
        return new self(
            id: $pickupPoint->id,
            name: $pickupPoint->name,
            address1: $pickupPoint->address,
            zipcode: $pickupPoint->zipcode,
            city: $pickupPoint->city,
            countryCode: $pickupPoint->country,
            address2: $pickupPoint->address2,
        );
    }
}
