<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

interface ShipmentTemplateResolverInterface
{
    /**
     * From the given list of shipment templates, the method will return the shipment templates that supports the given shipment
     *
     * @param list<ShipmentTemplate> $shipmentTemplates
     *
     * @return list<ShipmentTemplate>
     */
    public function resolve(Shipment $shipment, array $shipmentTemplates): array;
}
