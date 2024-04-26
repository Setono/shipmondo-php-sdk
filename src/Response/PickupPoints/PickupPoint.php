<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\PickupPoints;

use Setono\Shipmondo\Response\Response;

final class PickupPoint extends Response
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
        /**
         * @var list<string> $openingHours
         */
        public readonly array $openingHours,
        public readonly ?string $address2 = null,
    ) {
    }
}
