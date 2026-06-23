<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Resolver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Response\ShipmentTemplate\Parcel;
use Setono\Shipmondo\Response\ShipmentTemplate\Receiver;
use Setono\Shipmondo\Response\ShipmentTemplate\Sender;
use Setono\Shipmondo\Response\ShipmentTemplate\ShipmentTemplate;

final class ShipmentTemplateResolverTest extends TestCase
{
    /**
     * @param list<ShipmentTemplate> $shipmentTemplates
     */
    #[Test]
    #[DataProvider('resolvableShipments')]
    public function it_resolves(Shipment $shipment, array $shipmentTemplates, ShipmentTemplate $expectedShipmentTemplate): void
    {
        $resolver = new ShipmentTemplateResolver();
        self::assertEquals($expectedShipmentTemplate, $resolver->resolve($shipment, $shipmentTemplates));
    }

    /**
     * @param list<ShipmentTemplate> $shipmentTemplates
     */
    #[Test]
    #[DataProvider('unresolvableShipments')]
    public function it_does_not_resolve(Shipment $shipment, array $shipmentTemplates): void
    {
        $resolver = new ShipmentTemplateResolver();
        self::assertNull($resolver->resolve($shipment, $shipmentTemplates));
    }

    /**
     * @return \Generator<string, array{Shipment, list<ShipmentTemplate>, ShipmentTemplate}>
     */
    public static function resolvableShipments(): \Generator
    {
        yield 'resolvable' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100),
            [new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 100)])],
            new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 100)]),
        ];

        yield 'resolvable with multiple parcels' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100, divisible: true),
            [new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(2, weight: 50)])],
            new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(2, weight: 50)]),
        ];

        yield 'resolvable with multiple parcels 2' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100, divisible: true),
            [new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 75)])],
            new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 75)]),
        ];

        yield 'resolvable with multiple supporting shipment templates' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100, divisible: true),
            [
                new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 75)]),
                new ShipmentTemplate(id: 2, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 50)]),
            ],
            new ShipmentTemplate(id: 2, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 50), new Parcel(1, weight: 50)]),
        ];

        yield 'resolvable with no parcels (country match is enough)' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100),
            [new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [])],
            new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: []),
        ];
    }

    /**
     * @return \Generator<string, array{Shipment, list<ShipmentTemplate>}>
     */
    public static function unresolvableShipments(): \Generator
    {
        yield 'no shipment templates' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'SE', weight: 100),
            [],
        ];

        yield 'no sender country match' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'SE', weight: 100),
            [new ShipmentTemplate(id: 1, name: 'PostNord', sender: new Sender('SE'), receiver: new Receiver('DK'))],
        ];

        yield 'no receiver country match' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'SE', weight: 100),
            [new ShipmentTemplate(id: 1, name: 'PostNord', sender: new Sender('DK'), receiver: new Receiver('DK'))],
        ];

        yield 'not enough weight' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100),
            [new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(1, weight: 50)])],
        ];

        yield 'not divisible' => [
            new Shipment(shippingMethod: 'GLS', senderCountry: 'DK', receiverCountry: 'DK', weight: 100, divisible: false),
            [new ShipmentTemplate(id: 1, name: 'GLS', sender: new Sender('DK'), receiver: new Receiver('DK'), parcels: [new Parcel(2, weight: 50)])],
        ];
    }
}
