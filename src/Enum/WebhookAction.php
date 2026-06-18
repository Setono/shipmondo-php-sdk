<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Enum;

enum WebhookAction: string
{
    case Create = 'create';
    case Cancel = 'cancel';
    case StatusUpdate = 'status_update';
    case CreateFulfillment = 'create_fulfillment';
    case CreateShipment = 'create_shipment';
    case PaymentCaptured = 'payment_captured';
    case PaymentVoided = 'payment_voided';
    case Delete = 'delete';
    case Latest = 'latest';
    case Delivered = 'delivered';
}
