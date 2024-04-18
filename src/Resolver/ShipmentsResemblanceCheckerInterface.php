<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

interface ShipmentsResemblanceCheckerInterface
{
    /**
     * Returns true if the given shipping method and shipment template resemble each other
     *
     * @param string $shippingMethod The name of the shipping method
     * @param string $shipmentTemplate The name of the shipment template
     */
    public function compares(string $shippingMethod, string $shipmentTemplate): bool;
}
