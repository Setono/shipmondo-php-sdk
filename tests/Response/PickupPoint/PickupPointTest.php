<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\PickupPoint;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PickupPointTest extends TestCase
{
    #[Test]
    public function it_serializes_its_typed_fields_and_leaves_out_the_raw_response(): void
    {
        $pickupPoint = self::pickupPoint();
        $pickupPoint->raw = ['id' => '95115', 'carrier_code' => 'gls'];

        self::assertSame(
            '{"id":"95115","number":"95115","companyName":"Spar Kornblomstvej","name":"Spar Kornblomstvej","address":"Kornblomstvej 50","zipcode":"9000","city":"Aalborg","country":"DK","openingHours":["Monday: 08:00-20:00","Tuesday: 08:00-20:00"],"address2":"Pakkeshop: 95115"}',
            json_encode($pickupPoint, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    #[DataProvider('pickupPoints')]
    public function it_is_rebuilt_from_its_json(PickupPoint $pickupPoint): void
    {
        $data = json_decode(json_encode($pickupPoint, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        self::assertEquals($pickupPoint, PickupPoint::fromArray($data));
    }

    /**
     * @return \Generator<string, array{PickupPoint}>
     */
    public static function pickupPoints(): \Generator
    {
        yield 'with a second address line' => [self::pickupPoint()];
        yield 'without a second address line' => [self::pickupPoint(address2: null)];
        yield 'without opening hours' => [self::pickupPoint(openingHours: [])];
    }

    #[Test]
    public function it_is_rebuilt_without_a_second_address_line_when_the_json_has_none(): void
    {
        $data = self::json();
        unset($data['address2']);

        self::assertNull(PickupPoint::fromArray($data)->address2);
    }

    #[Test]
    public function it_ignores_keys_it_does_not_model(): void
    {
        // e.g. a pickup point a consumer stored by JSON encoding it before it implemented \JsonSerializable
        $data = self::json() + ['raw' => ['id' => '95115', 'carrier_code' => 'gls'], 'distance' => null];

        self::assertEquals(self::pickupPoint(), PickupPoint::fromArray($data));
    }

    #[Test]
    public function it_is_rebuilt_with_an_empty_raw_response(): void
    {
        self::assertSame([], PickupPoint::fromArray(self::json())->raw);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    #[Test]
    #[DataProvider('invalidJson')]
    public function it_rejects_json_with_a_missing_or_mistyped_key(array $data, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        PickupPoint::fromArray($data);
    }

    /**
     * @return \Generator<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidJson(): \Generator
    {
        foreach (['id', 'number', 'companyName', 'name', 'address', 'zipcode', 'city', 'country', 'openingHours'] as $key) {
            $data = self::json();
            unset($data[$key]);

            yield sprintf('no %s', $key) => [$data, sprintf('The pickup point has no "%s"', $key)];
        }

        foreach (['id', 'number', 'companyName', 'name', 'address', 'zipcode', 'city', 'country'] as $key) {
            yield sprintf('%s is a number', $key) => [array_replace(self::json(), [$key => 95115]), sprintf('The pickup point\'s "%s" must be a string', $key)];
            yield sprintf('%s is null', $key) => [array_replace(self::json(), [$key => null]), sprintf('The pickup point\'s "%s" must be a string', $key)];
        }

        yield 'opening hours are a string' => [array_replace(self::json(), ['openingHours' => 'Monday: 08:00-20:00']), 'The pickup point\'s "openingHours" must be a list of strings'];
        yield 'opening hours are keyed' => [array_replace(self::json(), ['openingHours' => ['monday' => '08:00-20:00']]), 'The pickup point\'s "openingHours" must be a list of strings'];
        yield 'an opening hour is a number' => [array_replace(self::json(), ['openingHours' => ['Monday: 08:00-20:00', 8]]), 'The pickup point\'s "openingHours" must be a list of strings'];
        yield 'the second address line is a number' => [array_replace(self::json(), ['address2' => 95115]), 'The pickup point\'s "address2" must be a string or null'];
    }

    /**
     * @param list<string> $openingHours
     */
    private static function pickupPoint(
        ?string $address2 = 'Pakkeshop: 95115',
        array $openingHours = ['Monday: 08:00-20:00', 'Tuesday: 08:00-20:00'],
    ): PickupPoint {
        return new PickupPoint(
            id: '95115',
            number: '95115',
            companyName: 'Spar Kornblomstvej',
            name: 'Spar Kornblomstvej',
            address: 'Kornblomstvej 50',
            zipcode: '9000',
            city: 'Aalborg',
            country: 'DK',
            openingHours: $openingHours,
            address2: $address2,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function json(): array
    {
        return [
            'id' => '95115',
            'number' => '95115',
            'companyName' => 'Spar Kornblomstvej',
            'name' => 'Spar Kornblomstvej',
            'address' => 'Kornblomstvej 50',
            'zipcode' => '9000',
            'city' => 'Aalborg',
            'country' => 'DK',
            'openingHours' => ['Monday: 08:00-20:00', 'Tuesday: 08:00-20:00'],
            'address2' => 'Pakkeshop: 95115',
        ];
    }
}
