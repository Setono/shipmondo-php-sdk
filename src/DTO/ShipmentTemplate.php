<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO;

// todo this is not complete with properties. See https://app.shipmondo.com/api/public/v3/specification#/operations/shipment_templates_get
final class ShipmentTemplate
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
