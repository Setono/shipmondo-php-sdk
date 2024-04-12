<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplates;

final class Parcel
{
    public function __construct(
        public readonly int $quantity,
        /**
         * @var int|null $weight The weight of the parcel in grams
         */
        public readonly ?int $weight = null,
    ) {
    }
}
