<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Enum\PackingSlipFormat;
use Setono\Shipmondo\Request\SalesOrder\OrderLine;
use Setono\Shipmondo\Request\SalesOrder\PaymentDetails;
use Setono\Shipmondo\Request\SalesOrder\Recipient;
use Setono\Shipmondo\Request\SalesOrder\SalesOrderRequest;
use Setono\Shipmondo\Request\SalesOrder\Sender;
use Setono\Shipmondo\Request\SalesOrder\ServicePoint;
use Setono\Shipmondo\Response\SalesOrder\SalesOrder;
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
        self::assertInstanceOf(SalesOrder::class, $page->first());
        self::assertSame(1, $page->first()->id);
    }

    #[Test]
    public function it_maps_the_real_sales_orders_list_payload(): void
    {
        // Real sandbox payload for GET /sales_orders (two orders).
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders?page=1&per_page=20',
            self::fixture('sales_orders_list.json'),
            200,
            ['X-Current-Page' => '1', 'X-Per-Page' => '25', 'X-Total-Count' => '2', 'X-Total-Pages' => '1'],
        );

        $page = $this->client($http)->salesOrders()->getPage();

        self::assertCount(2, $page);
        self::assertSame(2, $page->totalCount);
        self::assertSame(1, $page->totalPages);

        $first = $page->first();
        self::assertNotNull($first);
        self::assertSame(37707009, $first->id);
        self::assertSame('sdk-probe-6a33bb0a6d37e', $first->raw['order_id']);
    }

    #[Test]
    public function it_fetches_a_sales_order_by_id_and_stamps_raw(): void
    {
        // Real sandbox payload for GET /sales_orders/{id}.
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders/37707008',
            self::fixture('sales_order.json'),
        );

        $salesOrder = $this->client($http)->salesOrders()->getById(37707008);

        self::assertSame(37707008, $salesOrder->id);
        // $raw keeps the original snake_case keys (the untouched API payload).
        self::assertSame('sdk-test-6a33b7caa74b4', $salesOrder->raw['order_id']);
        self::assertSame('open', $salesOrder->raw['order_status']);
        $shipTo = $salesOrder->raw['ship_to'];
        self::assertIsArray($shipTo);
        self::assertSame('SDK Test', $shipTo['name']);
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

    #[Test]
    public function it_builds_a_sales_order_incrementally(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');

        // Built piecemeal the way the Sylius plugin does: no-arg ctor, mutated over time,
        // a partially-filled address, and an order line mutated in place before it's appended.
        $request = new SalesOrderRequest();
        $request->orderId = '27000';
        $request->shipTo = new Recipient();
        $request->shipTo->name = 'Jane';
        $request->shipTo->city = 'Aalborg';

        $line = new OrderLine();
        $line->itemName = 'Widget';
        $line->quantity = 3;
        $request->orderLines[] = $line;

        $this->client($http)->salesOrders()->create($request);

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('27000', $body['order_id']);

        // Partially-filled address: only the set fields are serialized, the null ones are dropped.
        $shipTo = $body['ship_to'];
        self::assertIsArray($shipTo);
        self::assertSame('Jane', $shipTo['name']);
        self::assertSame('Aalborg', $shipTo['city']);
        self::assertArrayNotHasKey('address1', $shipTo);
        self::assertArrayNotHasKey('country_code', $shipTo);

        $orderLines = $body['order_lines'];
        self::assertIsArray($orderLines);
        self::assertIsArray($orderLines[0]);
        self::assertSame('Widget', $orderLines[0]['item_name']);
        self::assertSame(3, $orderLines[0]['quantity']);
        self::assertSame('item', $orderLines[0]['line_type']);
    }

    #[Test]
    public function it_serializes_the_sender_and_service_point(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');

        $request = new SalesOrderRequest(orderId: '27000');
        $request->sender = new Sender(name: 'ACME', address1: 'Depot 1', city: 'CPH', zipcode: '1000', countryCode: 'DK', vatId: 'DK999');
        $request->servicePoint = new ServicePoint(id: '95115', name: 'Spar', zipcode: '9000', city: 'Aalborg', countryCode: 'DK', carrierCode: 'gls');

        $this->client($http)->salesOrders()->create($request);

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);

        // The sender uses `vat_id` (ship_to / bill_to use `vat_no` instead) and null fields are stripped.
        self::assertSame(
            ['name' => 'ACME', 'address1' => 'Depot 1', 'city' => 'CPH', 'zipcode' => '1000', 'country_code' => 'DK', 'vat_id' => 'DK999'],
            $body['sender'],
        );
        self::assertSame(
            ['id' => '95115', 'name' => 'Spar', 'zipcode' => '9000', 'city' => 'Aalborg', 'country_code' => 'DK', 'carrier_code' => 'gls'],
            $body['service_point'],
        );
    }
}
