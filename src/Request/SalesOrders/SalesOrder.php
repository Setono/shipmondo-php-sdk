<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrders;

use Setono\Shipmondo\Request\Request;

final class SalesOrder extends Request
{
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
        public ?string $packingSlipFormat = null,
        public bool $enableCustoms = false,
        public bool $useItemWeight = true,
        public Address $shipTo = new Address(),
        public Address $billTo = new Address(),
        public ?Address $sender = null,
        public PaymentDetails $paymentDetails = new PaymentDetails(),
        public ?ServicePoint $servicePoint = null,
        /**
         * @var list<OrderLine> $orderLines
         */
        public array $orderLines = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return self::filter([
            'order_id' => $this->orderId,
            'ordered_at' => $this->orderedAt?->format(\DATE_ATOM),
            'source_name' => $this->sourceName,
            'order_note' => $this->orderNote,
            'archived' => $this->archived,
            'shipment_template_id' => $this->shipmentTemplateId,
            'return_shipment_template_id' => $this->returnShipmentTemplateId,
            'sales_order_packaging_id' => $this->salesOrderPackagingId,
            'bookkeeping_integration_id' => $this->bookkeepingIntegrationId,
            'packing_slip_format' => $this->packingSlipFormat,
            'enable_customs' => $this->enableCustoms,
            'use_item_weight' => $this->useItemWeight,
            'ship_to' => $this->shipTo,
            'bill_to' => $this->billTo,
            'sender' => $this->sender,
            'payment_details' => $this->paymentDetails,
            'service_point' => $this->servicePoint,
            'order_lines' => $this->orderLines,
        ]);
    }
}
