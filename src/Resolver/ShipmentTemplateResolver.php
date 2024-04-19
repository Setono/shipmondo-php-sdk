<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

final class ShipmentTemplateResolver implements ShipmentTemplateResolverInterface
{
    public function __construct(private readonly ShipmentsResemblanceCheckerInterface $shipmentsResemblanceChecker)
    {
    }

    /**
     * For each shipment template we first check if it supports the shipment.
     * If it does, we check if it is the best match based on the weight it supports.
     * The shipment template where the supported weight is closest to the shipment weight is returned.
     */
    public function resolve(Shipment $shipment, array $shipmentTemplates): ?ShipmentTemplate
    {
        $supportedShipmentTemplate = null;
        $weightDiff = \PHP_INT_MAX;

        foreach ($shipmentTemplates as $shipmentTemplate) {
            if (!$this->supportsShipment($shipment, $shipmentTemplate)) {
                continue;
            }

            if (null === $supportedShipmentTemplate) {
                $supportedShipmentTemplate = $shipmentTemplate;
            }

            $newWeightDiff = abs($shipmentTemplate->getTotalSupportedWeight() - $shipment->weight);
            if ($newWeightDiff < $weightDiff) {
                $supportedShipmentTemplate = $shipmentTemplate;
                $weightDiff = $newWeightDiff;
            }
        }

        return $supportedShipmentTemplate;
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
