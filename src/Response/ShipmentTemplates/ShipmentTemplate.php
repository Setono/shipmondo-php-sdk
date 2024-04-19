<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplates;

use Setono\Shipmondo\Response\Response;

final class ShipmentTemplate extends Response
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly Sender $sender,
        public readonly Receiver $receiver,
        /** @var list<Parcel> $parcels */
        public readonly array $parcels = [],
    ) {
    }

    public function getTotalSupportedWeight(): int
    {
        $totalWeight = null;

        foreach ($this->parcels as $parcel) {
            // We presume that when the weight is null the parcel can carry any weight
            if (null === $parcel->weight) {
                return \PHP_INT_MAX;
            }

            if (null === $totalWeight) {
                $totalWeight = 0;
            }

            $totalWeight += $parcel->quantity * $parcel->weight;
        }

        // We presume that when there are no parcels the shipment can carry any weight
        return $totalWeight ?? \PHP_INT_MAX;
    }
}
