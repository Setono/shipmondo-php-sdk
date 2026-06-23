<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Enum;

enum WebhookResourceName: string
{
    case Shipments = 'Shipments';
    case Orders = 'Orders';
    case ShipmentMonitor = 'Shipment Monitor';

    /**
     * The webhook actions Shipmondo supports for this resource. Use it to discover the valid
     * actions for a resource (e.g. to build a UI) or to pre-validate a combination before creating
     * a webhook — `WebhooksEndpoint::create()` rejects an unsupported pair before sending.
     *
     * @return list<WebhookAction>
     */
    public function actions(): array
    {
        return match ($this) {
            self::Shipments => [
                WebhookAction::Create,
                WebhookAction::Cancel,
            ],
            self::Orders => [
                WebhookAction::Create,
                WebhookAction::StatusUpdate,
                WebhookAction::CreateFulfillment,
                WebhookAction::CreateShipment,
                WebhookAction::PaymentCaptured,
                WebhookAction::PaymentVoided,
                WebhookAction::Delete,
            ],
            self::ShipmentMonitor => [
                WebhookAction::Latest,
                WebhookAction::Delivered,
            ],
        };
    }

    /**
     * Whether Shipmondo supports the given action for this resource.
     */
    public function supports(WebhookAction $action): bool
    {
        return in_array($action, $this->actions(), true);
    }
}
