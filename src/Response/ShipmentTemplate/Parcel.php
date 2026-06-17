<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplate;

use Setono\Shipmondo\Response\Resource;

final class Parcel extends Resource
{
    public function __construct(
        public readonly int $quantity,
        /**
         * Weight in grams. `null` means the parcel can carry any weight.
         */
        public readonly ?int $weight = null,
    ) {
    }
}
