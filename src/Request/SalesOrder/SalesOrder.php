<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use Setono\Shipmondo\Enum\PackingSlipFormat;
use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

/**
 * Typed request body for `POST /sales_orders`. The only required field is `order_id`; everything
 * else is optional and omitted from the serialized JSON when `null` / `[]` (via the `Payload`
 * null-skipping transformer). The `$orderedAt` date serializes to an ISO-8601 (DATE_ATOM) string.
 *
 * Note the `vat_no` / `vat_id` split between {@see Recipient} (ship_to/bill_to) and {@see Sender}.
 */
final class SalesOrder extends Payload
{
    /**
     * @param list<string> $tags
     * @param list<OrderLine> $orderLines
     */
    public function __construct(
        public readonly string $orderId,
        public readonly ?\DateTimeInterface $orderedAt = null,
        public readonly ?string $sourceName = null,
        public readonly ?string $orderNote = null,
        public readonly bool $archived = false,
        public readonly ?int $shipmentTemplateId = null,
        public readonly ?int $returnShipmentTemplateId = null,
        public readonly ?int $salesOrderPackagingId = null,
        public readonly ?int $bookkeepingIntegrationId = null,
        public readonly ?PackingSlipFormat $packingSlipFormat = null,
        public readonly bool $enableCustoms = false,
        public readonly bool $useItemWeight = true,
        public readonly array $tags = [],
        public readonly ?Recipient $shipTo = null,
        public readonly ?Recipient $billTo = null,
        public readonly ?Sender $sender = null,
        public readonly ?PaymentDetails $paymentDetails = null,
        public readonly array $orderLines = [],
        public readonly ?ServicePoint $servicePoint = null,
    ) {
        Assert::stringNotEmpty($orderId);
    }
}
