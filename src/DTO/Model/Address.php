<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO\Model;

final class Address implements \JsonSerializable
{
    public function __construct(
        public ?string $name = null,
        public ?string $attention = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?string $countryCode = null,
        public ?string $vatId = null,
        public ?string $email = null,
        public ?string $mobile = null,
        public ?string $telephone = null,
        public ?string $instruction = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'attention' => $this->attention,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'zip_code' => $this->zipCode,
            'city' => $this->city,
            'country_code' => $this->countryCode,
            'vat_id' => $this->vatId,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'telephone' => $this->telephone,
            'instruction' => $this->instruction,
        ]);
    }
}
