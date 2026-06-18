<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class ShipmentTemplatesEndpointTest extends ShipmondoTestCase
{
    #[Test]
    public function it_fetches_a_page_and_maps_the_real_payload(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/shipment_templates?page=1&per_page=20',
            self::fixture('shipment_templates_list.json'),
            200,
            ['X-Current-Page' => '1', 'X-Per-Page' => '20', 'X-Total-Count' => '22', 'X-Total-Pages' => '2'],
        );

        $page = $this->client($http)->shipmentTemplates()->getPage();

        self::assertCount(2, $page);
        self::assertSame(22, $page->totalCount);
        self::assertSame(2, $page->totalPages);

        $template = $page->first();
        self::assertNotNull($template);
        self::assertSame(31411785, $template->id);
        self::assertSame('DK - Bring - Home Delivery Parcel - 0-1 kg', $template->name);
        self::assertSame('DK', $template->sender->countryCode);
        self::assertSame('DK', $template->receiver->countryCode);
        self::assertCount(1, $template->parcels);
        self::assertSame(1, $template->parcels[0]->quantity);
        self::assertSame(1000, $template->parcels[0]->weight);
        self::assertSame(1000, $template->getTotalSupportedWeight());
    }

    #[Test]
    public function it_fetches_a_template_by_id_via_query_param(): void
    {
        // The ?id= endpoint returns a single-element array; getByQueryId unwraps it.
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/shipment_templates?id=31411785',
            self::fixture('shipment_template_by_id.json'),
        );

        $template = $this->client($http)->shipmentTemplates()->getById(31411785);

        self::assertSame(31411785, $template->id);
        self::assertSame('DK - Bring - Home Delivery Parcel - 0-1 kg', $template->name);
        self::assertSame(1000, $template->getTotalSupportedWeight());
        self::assertSame(self::BASE . '/shipment_templates?id=31411785', (string) $http->sentRequests[0]->getUri());
    }
}
