<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Response\ShipmentTemplates\Parcel;
use Setono\Shipmondo\Response\ShipmentTemplates\Receiver;
use Setono\Shipmondo\Response\ShipmentTemplates\Sender;
use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

final class ShipmentTemplateResolverTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider resolvableShipments
     *
     * @param list<ShipmentTemplate> $shipmentTemplates
     */
    public function it_resolves(Shipment $shipment, array $shipmentTemplates, array $expectedShipmentTemplates): void
    {
        $resolver = new ShipmentTemplateResolver(new SimilarTextBasedShipmentsResemblanceChecker(10));
        self::assertEquals($expectedShipmentTemplates, $resolver->resolve($shipment, $shipmentTemplates));
    }

    /**
     * @test
     *
     * @dataProvider unresolvableShipments
     *
     * @param list<ShipmentTemplate> $shipmentTemplates
     */
    public function it_does_not_resolve(Shipment $shipment, array $shipmentTemplates): void
    {
        $resolver = new ShipmentTemplateResolver(new SimilarTextBasedShipmentsResemblanceChecker(10));
        self::assertEmpty($resolver->resolve($shipment, $shipmentTemplates));
    }

    /**
     * @return \Generator<string, array{Shipment, list<ShipmentTemplate>, list<ShipmentTemplate>}>
     */
    public function resolvableShipments(): \Generator
    {
        yield 'resolvable' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'DK',
                weight: 100,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(1, weight: 100)],
                ),
            ],
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(1, weight: 100)],
                ),
            ],
        ];

        yield 'resolvable with multiple parcels' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'DK',
                weight: 100,
                divisible: true,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(2, weight: 50)],
                ),
            ],
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(2, weight: 50)],
                ),
            ],
        ];

        yield 'resolvable with multiple parcels 2' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'DK',
                weight: 100,
                divisible: true,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 75)],
                ),
            ],
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 75)],
                ),
            ],
        ];
    }

    /**
     * @return \Generator<string, array{Shipment, list<ShipmentTemplate>}>
     */
    public function unresolvableShipments(): \Generator
    {
        yield 'no shipment templates' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'SE',
                weight: 100,
            ),
            [],
        ];

        yield 'no sender country match' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'SE',
                weight: 100,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'PostNord',
                    sender: new Sender('SE'),
                    receiver: new Receiver('DK'),
                ),
            ],
        ];

        yield 'no receiver country match' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'SE',
                weight: 100,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'PostNord',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                ),
            ],
        ];

        yield 'no matching name' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'DK',
                weight: 100,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'DAO',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                ),
            ],
        ];

        yield 'not enough weight' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'DK',
                weight: 100,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(1, weight: 50)],
                ),
            ],
        ];

        yield 'not divisible' => [
            new Shipment(
                shippingMethod: 'GLS',
                senderCountry: 'DK',
                receiverCountry: 'DK',
                weight: 100,
                divisible: false,
            ),
            [
                new ShipmentTemplate(
                    id: 1,
                    name: 'GLS',
                    sender: new Sender('DK'),
                    receiver: new Receiver('DK'),
                    parcels: [new Parcel(2, weight: 50)],
                ),
            ],
        ];
    }
}
