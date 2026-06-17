<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Request\PickupPointSearch;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class PickupPointsEndpointTest extends ShipmondoTestCase
{
    #[Test]
    public function it_searches_pickup_points_from_a_bare_array(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/pickup_points?carrier_code=gls&country_code=DK&zipcode=1000',
            '[{"id":"7","number":"7","company_name":"GLS","name":"Kiosk","address":"Main 1","zipcode":"1000","city":"CPH","country":"DK","opening_hours":["mon 8-16"]}]',
        );

        $points = $this->client($http)->pickupPoints()->search(new PickupPointSearch('gls', 'DK', '1000'));

        self::assertCount(1, $points);
        self::assertSame('7', $points[0]->id);
        self::assertSame('GLS', $points[0]->companyName);
        self::assertSame('1000', $points[0]->zipcode);
        self::assertSame(['mon 8-16'], $points[0]->openingHours);
    }

    #[Test]
    public function it_omits_null_optional_query_parameters(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/pickup_points?carrier_code=gls&country_code=DK&zipcode=1000',
            '[]',
        );

        $this->client($http)->pickupPoints()->search(new PickupPointSearch('gls', 'DK', '1000'));

        // address + city were null, so they must not appear in the query string.
        self::assertSame(
            self::BASE . '/pickup_points?carrier_code=gls&country_code=DK&zipcode=1000',
            (string) $http->sentRequests[0]->getUri(),
        );
    }
}
