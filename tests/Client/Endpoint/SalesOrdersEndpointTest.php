<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Enum\PackingSlipFormat;
use Setono\Shipmondo\Request\SalesOrder\OrderLine;
use Setono\Shipmondo\Request\SalesOrder\PaymentDetails;
use Setono\Shipmondo\Request\SalesOrder\Recipient;
use Setono\Shipmondo\Request\SalesOrder\SalesOrder as SalesOrderRequest;
use Setono\Shipmondo\Response\SalesOrder\SalesOrder as SalesOrderResponse;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class SalesOrdersEndpointTest extends ShipmondoTestCase
{
    #[Test]
    public function it_fetches_a_page_with_pagination_headers(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders?page=1&per_page=20',
            '[{"id":1},{"id":2}]',
            200,
            ['X-Current-Page' => '1', 'X-Per-Page' => '20', 'X-Total-Count' => '42', 'X-Total-Pages' => '3'],
        );

        $page = $this->client($http)->salesOrders()->getPage();

        self::assertCount(2, $page);
        self::assertSame(1, $page->page);
        self::assertSame(20, $page->pageSize);
        self::assertSame(42, $page->totalCount);
        self::assertSame(3, $page->totalPages);
        self::assertInstanceOf(SalesOrderResponse::class, $page->first());
        self::assertSame(1, $page->first()->id);
    }

    #[Test]
    public function it_fetches_a_sales_order_by_id_and_stamps_raw(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders/125',
            '{"id":125,"order_id":"abc","ship_to":{"name":"John","country_code":"DK"}}',
        );

        $salesOrder = $this->client($http)->salesOrders()->getById(125);

        self::assertSame(125, $salesOrder->id);
        // $raw is camelCased so it lines up with the typed property names.
        self::assertSame('abc', $salesOrder->raw['orderId']);
        $shipTo = $salesOrder->raw['shipTo'];
        self::assertIsArray($shipTo);
        self::assertSame('John', $shipTo['name']);
    }

    #[Test]
    public function it_creates_a_sales_order_and_serializes_snake_case_json(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":999}');

        $created = $this->client($http)->salesOrders()->create(new SalesOrderRequest(
            orderId: '27000',
            orderedAt: new \DateTimeImmutable('2018-10-17T13:25:44+00:00'),
            packingSlipFormat: PackingSlipFormat::A4Pdf,
            shipTo: new Recipient(name: 'John', address1: 'Main 1', city: 'CPH', zipcode: '1000', countryCode: 'DK', vatNo: 'DK123'),
            orderLines: [new OrderLine(itemName: 'Widget', currencyCode: 'DKK', quantity: 2)],
            paymentDetails: new PaymentDetails(amountIncludingVat: '2000.0', vatAmount: '400.0', currencyCode: 'DKK'),
        ));

        self::assertSame(999, $created->id);

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('27000', $body['order_id']);
        self::assertSame('2018-10-17T13:25:44+00:00', $body['ordered_at']);
        self::assertSame('a4_pdf', $body['packing_slip_format']);
        self::assertFalse($body['archived']);
        self::assertTrue($body['use_item_weight']);
        self::assertSame(['name' => 'John', 'address1' => 'Main 1', 'city' => 'CPH', 'zipcode' => '1000', 'country_code' => 'DK', 'vat_no' => 'DK123'], $body['ship_to']);

        $orderLines = $body['order_lines'];
        self::assertIsArray($orderLines);
        self::assertIsArray($orderLines[0]);
        self::assertSame('item', $orderLines[0]['line_type']);
        self::assertSame('Widget', $orderLines[0]['item_name']);

        $paymentDetails = $body['payment_details'];
        self::assertIsArray($paymentDetails);
        self::assertSame('2000.0', $paymentDetails['amount_including_vat']);

        // null / empty-array properties are stripped, never serialized.
        self::assertArrayNotHasKey('source_name', $body);
        self::assertArrayNotHasKey('sender', $body);
        self::assertArrayNotHasKey('tags', $body);
    }
}
