<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplate;

use Setono\Shipmondo\Response\Resource;

final class Sender extends Resource
{
    public function __construct(
        public readonly string $countryCode,
    ) {
    }
}
