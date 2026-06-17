<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Enum;

enum WebhookResourceName: string
{
    case Shipments = 'Shipments';
    case Orders = 'Orders';
    case ShipmentMonitor = 'Shipment Monitor';
}
