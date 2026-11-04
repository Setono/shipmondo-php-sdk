<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\PickupPoint;

use Setono\Shipmondo\Response\Resource;
use Webmozart\Assert\Assert;

/**
 * A pickup point is often kept after the search, e.g. the one a customer chose in a checkout. Its JSON
 * ({@see self::jsonSerialize()}) holds its typed fields, and {@see self::fromArray()} rebuilds it from that JSON.
 */
final class PickupPoint extends Resource implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $number,
        public readonly string $companyName,
        public readonly string $name,
        public readonly string $address,
        /**
         * We need to spell this wrong because Shipmondo spelled it wrong in the response
         */
        public readonly string $zipcode,
        public readonly string $city,
        public readonly string $country,
        /**
         * @var list<string> $openingHours
         */
        public readonly array $openingHours,
        public readonly ?string $address2 = null,
    ) {
    }

    /**
     * Rebuilds a pickup point from its JSON. Keys it doesn't model, such as `raw`, are ignored, and `address2` may be
     * left out. The rebuilt pickup point has an empty {@see Resource::$raw}.
     *
     * @param array<array-key, mixed> $data
     *
     * @throws \InvalidArgumentException if a key is missing or has the wrong type
     */
    public static function fromArray(array $data): self
    {
        Assert::keyExists($data, 'openingHours', 'The pickup point has no "openingHours"');
        $openingHours = $data['openingHours'];
        Assert::isList($openingHours, 'The pickup point\'s "openingHours" must be a list of strings');
        Assert::allString($openingHours, 'The pickup point\'s "openingHours" must be a list of strings');

        $address2 = $data['address2'] ?? null;
        Assert::nullOrString($address2, 'The pickup point\'s "address2" must be a string or null');

        return new self(
            id: self::string($data, 'id'),
            number: self::string($data, 'number'),
            companyName: self::string($data, 'companyName'),
            name: self::string($data, 'name'),
            address: self::string($data, 'address'),
            zipcode: self::string($data, 'zipcode'),
            city: self::string($data, 'city'),
            country: self::string($data, 'country'),
            openingHours: $openingHours,
            address2: $address2,
        );
    }

    /**
     * The typed fields, keyed by their property names. Never {@see Resource::$raw}
     *
     * @return array{id: string, number: string, companyName: string, name: string, address: string, zipcode: string, city: string, country: string, openingHours: list<string>, address2: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'companyName' => $this->companyName,
            'name' => $this->name,
            'address' => $this->address,
            'zipcode' => $this->zipcode,
            'city' => $this->city,
            'country' => $this->country,
            'openingHours' => $this->openingHours,
            'address2' => $this->address2,
        ];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private static function string(array $data, string $key): string
    {
        Assert::keyExists($data, $key, sprintf('The pickup point has no "%s"', $key));
        $value = $data[$key];
        Assert::string($value, sprintf('The pickup point\'s "%s" must be a string', $key));

        return $value;
    }
}
