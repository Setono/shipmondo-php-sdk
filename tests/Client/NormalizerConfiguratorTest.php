<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Enum\OrderLineType;
use Setono\Shipmondo\Enum\PackingSlipFormat;
use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Request\Payload;
use Setono\Shipmondo\Request\SalesOrder\OrderLine;
use Setono\Shipmondo\Request\SalesOrder\PaymentDetails;
use Setono\Shipmondo\Request\SalesOrder\Recipient;
use Setono\Shipmondo\Request\SalesOrder\SalesOrderRequest;
use Setono\Shipmondo\Request\SalesOrder\Sender;
use Setono\Shipmondo\Request\SalesOrder\ServicePoint;
use Setono\Shipmondo\Request\Webhook\WebhookRequest;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

/**
 * Pins the key Shipmondo receives for every property of every request body, with each property set. The keys are the
 * ones in Shipmondo's request schemas (CreateSalesOrderRequest and the schemas it refers to, and WebhookCreate)
 */
final class NormalizerConfiguratorTest extends ShipmondoTestCase
{
    #[Test]
    public function it_sends_every_field_of_a_sales_order_under_shipmondos_key(): void
    {
        self::assertSame(
            [
                'order_id' => '27000',
                'ordered_at' => '2026-11-25T09:30:15+01:00',
                'source_name' => 'Webshop',
                'order_note' => 'Leave at the door',
                'archived' => true,
                'shipment_template_id' => 11,
                'return_shipment_template_id' => 12,
                'sales_order_packaging_id' => 13,
                'bookkeeping_integration_id' => 14,
                'packing_slip_format' => '10x19_pdf',
                'enable_customs' => true,
                'use_item_weight' => false,
                'tags' => ['b2b', 'priority'],
                'ship_to' => self::recipientKeys('Jane'),
                'bill_to' => self::recipientKeys('John'),
                'sender' => [
                    'name' => 'ACME',
                    'address1' => 'Depot 1',
                    'city' => 'Copenhagen',
                    'zipcode' => '1000',
                    'country_code' => 'DK',
                    'attention' => 'Warehouse',
                    'vat_id' => 'DK999',
                    'email' => 'shop@example.com',
                    'mobile' => '+4511111111',
                    'telephone' => '+4522222222',
                    'address2' => 'Gate 2',
                ],
                'payment_details' => [
                    'amount_including_vat' => '125.0',
                    'vat_amount' => '25.0',
                    'currency_code' => 'DKK',
                    'amount_excluding_vat' => '100.0',
                    'authorized_amount' => '125.0',
                    'vat_percent' => '0.25',
                    'payment_method' => 'card',
                    'transaction_id' => 'tx-1',
                    'payment_gateway_id' => '5314',
                ],
                'order_lines' => [[
                    'item_name' => 'Widget',
                    'currency_code' => 'DKK',
                    'line_type' => 'shipping',
                    'quantity' => 2.5,
                    'item_sku' => 'W-1',
                    'item_variant_code' => 'W-1-RED',
                    'unit_price_excluding_vat' => '40.0',
                    'discount_amount_excluding_vat' => '5.0',
                    'vat_percent' => '0.25',
                    'unit_weight' => 500,
                    'item_barcode' => '5701234567890',
                    'item_bin' => 'A-1',
                    'image_url' => 'https://example.com/widget.png',
                    'cost_price' => '20.0',
                    'country_code_of_origin' => 'DK',
                    'customs_commodity_code' => '9503',
                    'customs_description' => 'Toy',
                ]],
                'service_point' => [
                    'id' => '95115',
                    'name' => 'Spar Kornblomstvej',
                    'address1' => 'Kornblomstvej 50',
                    'zipcode' => '9000',
                    'city' => 'Aalborg',
                    'country_code' => 'DK',
                    'address2' => 'Pakkeshop: 95115',
                    'carrier_code' => 'gls',
                ],
            ],
            $this->sentBody(new SalesOrderRequest(
                orderId: '27000',
                // DATE_ATOM, so the microseconds are left out
                orderedAt: new \DateTimeImmutable('2026-11-25T09:30:15.123456+01:00'),
                sourceName: 'Webshop',
                orderNote: 'Leave at the door',
                archived: true,
                shipmentTemplateId: 11,
                returnShipmentTemplateId: 12,
                salesOrderPackagingId: 13,
                bookkeepingIntegrationId: 14,
                packingSlipFormat: PackingSlipFormat::Pdf10x19,
                enableCustoms: true,
                useItemWeight: false,
                tags: ['b2b', 'priority'],
                shipTo: self::recipient('Jane'),
                billTo: self::recipient('John'),
                sender: new Sender(
                    name: 'ACME',
                    address1: 'Depot 1',
                    city: 'Copenhagen',
                    zipcode: '1000',
                    countryCode: 'DK',
                    attention: 'Warehouse',
                    vatId: 'DK999',
                    email: 'shop@example.com',
                    mobile: '+4511111111',
                    telephone: '+4522222222',
                    address2: 'Gate 2',
                ),
                paymentDetails: new PaymentDetails(
                    amountIncludingVat: '125.0',
                    vatAmount: '25.0',
                    currencyCode: 'DKK',
                    amountExcludingVat: '100.0',
                    authorizedAmount: '125.0',
                    vatPercent: '0.25',
                    paymentMethod: 'card',
                    transactionId: 'tx-1',
                    paymentGatewayId: '5314',
                ),
                orderLines: [new OrderLine(
                    itemName: 'Widget',
                    currencyCode: 'DKK',
                    lineType: OrderLineType::Shipping,
                    quantity: 2.5,
                    itemSku: 'W-1',
                    itemVariantCode: 'W-1-RED',
                    unitPriceExcludingVat: '40.0',
                    discountAmountExcludingVat: '5.0',
                    vatPercent: '0.25',
                    unitWeight: 500,
                    itemBarcode: '5701234567890',
                    itemBin: 'A-1',
                    imageUrl: 'https://example.com/widget.png',
                    costPrice: '20.0',
                    countryCodeOfOrigin: 'DK',
                    customsCommodityCode: '9503',
                    customsDescription: 'Toy',
                )],
                servicePoint: new ServicePoint(
                    id: '95115',
                    name: 'Spar Kornblomstvej',
                    address1: 'Kornblomstvej 50',
                    zipcode: '9000',
                    city: 'Aalborg',
                    countryCode: 'DK',
                    address2: 'Pakkeshop: 95115',
                    carrierCode: 'gls',
                ),
            )),
        );
    }

    #[Test]
    public function it_sends_every_field_of_a_webhook_under_shipmondos_key(): void
    {
        self::assertSame(
            [
                'name' => 'sdk-test',
                'endpoint' => 'https://example.com/webhook',
                'key' => 'a-sufficiently-long-webhook-signing-key',
                'action' => 'create',
                'resource_name' => 'Shipment Monitor',
            ],
            $this->sentBody(new WebhookRequest(
                name: 'sdk-test',
                endpoint: 'https://example.com/webhook',
                key: 'a-sufficiently-long-webhook-signing-key',
                action: WebhookAction::Create,
                resourceName: WebhookResourceName::ShipmentMonitor,
            )),
        );
    }

    private static function recipient(string $name): Recipient
    {
        return new Recipient(
            name: $name,
            address1: 'Main 1',
            city: 'Aalborg',
            zipcode: '9000',
            countryCode: 'DK',
            attention: 'Reception',
            email: 'jane@example.com',
            mobile: '+4533333333',
            telephone: '+4544444444',
            vatNo: 'DK123',
            instruction: 'Ring twice',
            address2: '2nd floor',
        );
    }

    /**
     * @return array<string, string>
     */
    private static function recipientKeys(string $name): array
    {
        return [
            'name' => $name,
            'address1' => 'Main 1',
            'city' => 'Aalborg',
            'zipcode' => '9000',
            'country_code' => 'DK',
            'attention' => 'Reception',
            'email' => 'jane@example.com',
            'mobile' => '+4533333333',
            'telephone' => '+4544444444',
            'vat_no' => 'DK123',
            'instruction' => 'Ring twice',
            'address2' => '2nd floor',
        ];
    }

    private function sentBody(Payload $payload): mixed
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/anything', '{}');

        $this->client($http)->post('anything', $payload);

        return json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
