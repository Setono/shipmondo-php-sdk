<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplates;

use Setono\Shipmondo\Response\Response;

final class ShipmentTemplate extends Response
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
