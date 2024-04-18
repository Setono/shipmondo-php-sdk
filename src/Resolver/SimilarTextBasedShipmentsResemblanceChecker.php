<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

final class SimilarTextBasedShipmentsResemblanceChecker implements ShipmentsResemblanceCheckerInterface
{
    public function __construct(private readonly float $threshold)
    {
    }

    public function compares(string $shippingMethod, string $shipmentTemplate): bool
    {
        similar_text($shippingMethod, $shipmentTemplate, $percentage);

        return $percentage > $this->threshold;
    }
}
