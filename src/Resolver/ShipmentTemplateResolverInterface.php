<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

interface ShipmentTemplateResolverInterface
{
    /**
     * From the given list of shipment templates, the method will return the best supporting shipment template or null if none are supporting
     *
     * @param list<ShipmentTemplate> $shipmentTemplates
     */
    public function resolve(Shipment $shipment, array $shipmentTemplates): ?ShipmentTemplate;
}
