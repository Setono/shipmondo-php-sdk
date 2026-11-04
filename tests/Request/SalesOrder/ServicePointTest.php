<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\SalesOrder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Response\PickupPoint\PickupPoint;

final class ServicePointTest extends TestCase
{
    #[Test]
    #[DataProvider('secondAddressLines')]
    public function it_is_the_service_point_of_a_sales_order_delivered_to_a_pickup_point(?string $address2): void
    {
        $pickupPoint = new PickupPoint(
            id: '95115',
            number: '95115',
            companyName: 'Spar',
            name: 'Spar Kornblomstvej',
            address: 'Kornblomstvej 50',
            zipcode: '9000',
            city: 'Aalborg',
            country: 'DK',
            openingHours: ['Monday: 08:00-20:00'],
            address2: $address2,
        );

        self::assertEquals(
            new ServicePoint(
                id: '95115',
                name: 'Spar Kornblomstvej',
                address1: 'Kornblomstvej 50',
                zipcode: '9000',
                city: 'Aalborg',
                countryCode: 'DK',
                address2: $address2,
            ),
            ServicePoint::fromPickupPoint($pickupPoint),
        );
    }

    /**
     * @return \Generator<string, array{?string}>
     */
    public static function secondAddressLines(): \Generator
    {
        yield 'with a second address line' => ['Pakkeshop: 95115'];
        yield 'without a second address line' => [null];
    }
}
