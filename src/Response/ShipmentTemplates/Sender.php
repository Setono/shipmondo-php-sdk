<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplates;

final class Sender
{
    public function __construct(public readonly string $countryCode)
    {
    }
}
