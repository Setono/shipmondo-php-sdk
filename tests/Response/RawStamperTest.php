<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Response\ShipmentTemplate\Parcel;
use Setono\Shipmondo\Response\ShipmentTemplate\Receiver;
use Setono\Shipmondo\Response\ShipmentTemplate\Sender;
use Setono\Shipmondo\Response\ShipmentTemplate\ShipmentTemplate;

#[CoversClass(RawStamper::class)]
final class RawStamperTest extends TestCase
{
    #[Test]
    public function it_stamps_the_top_level_and_nested_resources(): void
    {
        $template = new ShipmentTemplate(
            id: 1,
            name: 'GLS',
            sender: new Sender('DK'),
            receiver: new Receiver('SE'),
            parcels: [new Parcel(1, 100), new Parcel(2, 200)],
        );

        $data = [
            'id' => 1,
            'name' => 'GLS',
            'sender' => ['countryCode' => 'DK'],
            'receiver' => ['countryCode' => 'SE'],
            'parcels' => [
                ['quantity' => 1, 'weight' => 100],
                ['quantity' => 2, 'weight' => 200],
            ],
        ];

        RawStamper::stamp($template, $data);

        // top-level resource carries the full body
        self::assertSame($data, $template->raw);
        // nested resources carry their own slice
        self::assertSame(['countryCode' => 'DK'], $template->sender->raw);
        self::assertSame(['countryCode' => 'SE'], $template->receiver->raw);
        // list items are matched by position
        self::assertSame(['quantity' => 1, 'weight' => 100], $template->parcels[0]->raw);
        self::assertSame(['quantity' => 2, 'weight' => 200], $template->parcels[1]->raw);
    }
}
