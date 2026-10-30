<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PickupPointSearchTest extends TestCase
{
    #[Test]
    public function it_emits_the_snake_case_query_parameters(): void
    {
        $search = new PickupPointSearch('gls', 'DK', '9000', 'Kornblomstvej 1', 'Aalborg');

        self::assertSame(
            ['carrier_code' => 'gls', 'country_code' => 'DK', 'zipcode' => '9000', 'address' => 'Kornblomstvej 1', 'city' => 'Aalborg'],
            $search->toArray(),
        );
    }

    #[Test]
    #[DataProvider('emptyValues')]
    public function it_rejects_an_empty_value(string $carrierCode, string $countryCode, string $zipCode, ?string $address, ?string $city, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new PickupPointSearch($carrierCode, $countryCode, $zipCode, $address, $city);
    }

    /**
     * @return \Generator<string, array{string, string, string, ?string, ?string, string}>
     */
    public static function emptyValues(): \Generator
    {
        yield 'carrier code' => ['', 'DK', '9000', null, null, 'The carrier code must be a non-empty string'];
        yield 'country code' => ['gls', '', '9000', null, null, 'The country code must be a non-empty string'];
        yield 'zip code' => ['gls', 'DK', '', null, null, 'The zip code must be a non-empty string'];
        yield 'address' => ['gls', 'DK', '9000', '', null, 'The address must be a non-empty string'];
        yield 'city' => ['gls', 'DK', '9000', null, '', 'The city must be a non-empty string'];
    }
}
