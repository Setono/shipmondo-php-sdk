<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplates;

// todo this is not complete with properties. See https://app.shipmondo.com/api/public/v3/specification#/operations/shipment_templates_get
use Setono\Shipmondo\Response\Response;

final class ShipmentTemplate extends Response
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
