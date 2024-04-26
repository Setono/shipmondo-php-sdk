<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\ShipmentTemplates;

use PHPUnit\Framework\TestCase;

final class ShipmentTemplateTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideParcels
     *
     * @param list<Parcel> $parcels
     */
    public function it_returns_total_weight(int $expectedWeight, array $parcels): void
    {
        $shipmentTemplate = new ShipmentTemplate(
            id: 1,
            name: 'GLS',
            sender: new Sender('DK'),
            receiver: new Receiver('DK'),
            parcels: $parcels,
        );

        self::assertEquals($expectedWeight, $shipmentTemplate->getTotalSupportedWeight());
    }

    /**
     * @return \Generator<array-key, array{int, list<Parcel>}>
     */
    public function provideParcels(): \Generator
    {
        yield [100, [new Parcel(1, weight: 100)]];
        yield [200, [new Parcel(2, weight: 100)]];
        yield [250, [new Parcel(2, weight: 100), new Parcel(1, weight: 50)]];
        yield [\PHP_INT_MAX, [new Parcel(1)]];
        yield [\PHP_INT_MAX, [new Parcel(1), new Parcel(1)]];
        yield [\PHP_INT_MAX, []];
    }
}
