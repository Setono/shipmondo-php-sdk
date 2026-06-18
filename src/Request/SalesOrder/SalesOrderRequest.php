<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Enum\PackingSlipFormat;
use Setono\Shipmondo\Request\Payload;

/**
 * Typed request body for `POST /sales_orders`. All properties are optional and mutable, so the
 * request can be built incrementally (`new SalesOrderRequest()` then assign fields / append to
 * `$orderLines`) or in one named-argument call. `null` / `[]` values are omitted from the
 * serialized JSON (via the `Payload` normalizer); the `$orderedAt` date serializes to an ISO-8601
 * (DATE_ATOM) string. Required fields are enforced by the API, not at construction.
 *
 * Note the `vat_no` / `vat_id` split between {@see Recipient} (ship_to/bill_to) and {@see Sender}.
 */
final class SalesOrderRequest extends Payload
{
    /**
     * @param list<string> $tags
     * @param list<OrderLine> $orderLines
     */
    public function __construct(
        public ?string $orderId = null,
        public ?\DateTimeInterface $orderedAt = null,
        public ?string $sourceName = null,
        public ?string $orderNote = null,
        public bool $archived = false,
        public ?int $shipmentTemplateId = null,
        public ?int $returnShipmentTemplateId = null,
        public ?int $salesOrderPackagingId = null,
        public ?int $bookkeepingIntegrationId = null,
        public ?PackingSlipFormat $packingSlipFormat = null,
        public bool $enableCustoms = false,
        public bool $useItemWeight = true,
        public array $tags = [],
        public ?Recipient $shipTo = null,
        public ?Recipient $billTo = null,
        public ?Sender $sender = null,
        public ?PaymentDetails $paymentDetails = null,
        public array $orderLines = [],
        public ?ServicePoint $servicePoint = null,
    ) {
    }
}
