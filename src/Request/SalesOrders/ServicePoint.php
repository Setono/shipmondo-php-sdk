<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrders;

use Setono\Shipmondo\Request\Request;

final class ServicePoint extends Request
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $address1,
        public readonly string $zipCode,
        public readonly string $city,
        public readonly string $countryCode,
        public readonly ?string $address2,
    ) {
    }

    public function jsonSerialize(): array
    {
        return self::filter([
            'id' => $this->id,
            'name' => $this->name,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'zipcode' => $this->zipCode,
            'city' => $this->city,
            'country_code' => $this->countryCode,
        ]);
    }
}
