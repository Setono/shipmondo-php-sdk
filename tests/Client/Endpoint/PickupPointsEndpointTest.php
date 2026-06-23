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
        // Real sandbox payload (GLS pickup points near zip 9000).
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/pickup_points?carrier_code=gls&country_code=DK&zipcode=9000',
            self::fixture('pickup_points.json'),
        );

        $points = $this->client($http)->pickupPoints()->search(new PickupPointSearch('gls', 'DK', '9000'));

        self::assertCount(2, $points);
        self::assertSame('95115', $points[0]->id);
        self::assertSame('95115', $points[0]->number);
        self::assertSame('Spar Kornblomstvej', $points[0]->companyName);
        self::assertSame('9000', $points[0]->zipcode);
        self::assertSame('Aalborg', $points[0]->city);
        self::assertSame('DK', $points[0]->country);
        self::assertNotEmpty($points[0]->openingHours);
        self::assertSame('Pakkeshop: 95115', $points[0]->address2);
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

    #[Test]
    public function it_skips_non_array_rows_in_the_response(): void
    {
        $decoded = json_decode(self::fixture('pickup_points.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $decoded[] = 'not-an-object'; // a stray scalar row the SDK must skip rather than map

        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/pickup_points?carrier_code=gls&country_code=DK&zipcode=9000',
            json_encode($decoded, \JSON_THROW_ON_ERROR),
        );

        $points = $this->client($http)->pickupPoints()->search(new PickupPointSearch('gls', 'DK', '9000'));

        // Only the two valid pickup points come back; the scalar row was skipped.
        self::assertCount(2, $points);
    }
}
