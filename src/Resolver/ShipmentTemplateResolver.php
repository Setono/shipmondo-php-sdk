<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

final class ShipmentTemplateResolver implements ShipmentTemplateResolverInterface
{
    public function __construct(private readonly ShipmentsResemblanceCheckerInterface $shipmentsResemblanceChecker)
    {
    }

    public function resolve(Shipment $shipment, array $shipmentTemplates): array
    {
        $supportingShipmentTemplates = [];

        foreach ($shipmentTemplates as $shipmentTemplate) {
            if ($this->supportsShipment($shipment, $shipmentTemplate)) {
                $supportingShipmentTemplates[] = $shipmentTemplate;
            }
        }

        return $supportingShipmentTemplates;
    }

    /**
     * Returns true if the given ShipmentTemplate supports the given Shipment
     */
    private function supportsShipment(Shipment $shipment, ShipmentTemplate $shipmentTemplate): bool
    {
        if ($shipmentTemplate->sender->countryCode !== $shipment->senderCountry) {
            return false;
        }

        if ($shipmentTemplate->receiver->countryCode !== $shipment->receiverCountry) {
            return false;
        }

        if (!$this->shipmentsResemblanceChecker->compares($shipment->shippingMethod, $shipmentTemplate->name)) {
            return false;
        }

        if ([] === $shipmentTemplate->parcels) {
            return true;
        }

        $remainingWeight = $shipment->weight;

        $parcelsUsed = 0;

        foreach ($shipmentTemplate->parcels as $parcel) {
            $parcelsUsed += $parcel->quantity;

            // we presume that when the weight is null the parcel can carry any weight
            $remainingWeight -= $parcel->quantity * ($parcel->weight ?? $remainingWeight);

            if ($remainingWeight <= 0) {
                break;
            }
        }

        return $remainingWeight <= 0 && ($parcelsUsed <= 1 || $shipment->divisible);
    }
}
