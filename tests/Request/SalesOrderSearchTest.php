<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SalesOrderSearch::class)]
final class SalesOrderSearchTest extends TestCase
{
    #[Test]
    public function it_sends_every_filter_by_its_query_name(): void
    {
        $search = new SalesOrderSearch(
            id: 37707008,
            orderId: '000001234',
            reference: 'REF-1',
            billToEmail: 'bill@example.com',
            billToMobile: '+4511111111',
            shipToEmail: 'ship@example.com',
            shipToMobile: '+4522222222',
            archived: false,
            createdAtMin: new \DateTimeImmutable('2026-11-07T10:00:00.250+01:00'),
            createdAtMax: new \DateTimeImmutable('2026-11-07T11:00:00+01:00'),
            updatedAtMin: new \DateTimeImmutable('2026-11-07T12:00:00+01:00'),
            updatedAtMax: new \DateTimeImmutable('2026-11-07T13:00:00+01:00'),
        );

        self::assertSame([
            'id' => 37707008,
            'order_id' => '000001234',
            'reference' => 'REF-1',
            'bill_to_email' => 'bill@example.com',
            'bill_to_mobile' => '+4511111111',
            'ship_to_email' => 'ship@example.com',
            'ship_to_mobile' => '+4522222222',
            'archived' => 'false',
            // Shipmondo's documented format, with milliseconds
            'created_at_min' => '2026-11-07T10:00:00.250+01:00',
            'created_at_max' => '2026-11-07T11:00:00.000+01:00',
            'updated_at_min' => '2026-11-07T12:00:00.000+01:00',
            'updated_at_max' => '2026-11-07T13:00:00.000+01:00',
        ], $search->toArray());
    }

    #[Test]
    public function it_sends_null_for_the_filters_it_is_not_given_so_the_client_leaves_them_out(): void
    {
        self::assertSame(
            ['order_id' => '000001234'],
            array_filter((new SalesOrderSearch(orderId: '000001234'))->toArray(), static fn (mixed $value): bool => null !== $value),
        );
        self::assertCount(12, array_filter((new SalesOrderSearch())->toArray(), static fn (mixed $value): bool => null === $value));
    }

    #[Test]
    public function it_sends_true_for_archived_sales_orders(): void
    {
        self::assertSame('true', (new SalesOrderSearch(archived: true))->toArray()['archived']);
    }

    /**
     * @param \Closure(): SalesOrderSearch $search
     */
    #[Test]
    #[DataProvider('invalidSearches')]
    public function it_refuses_an_invalid_filter(\Closure $search, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $search();
    }

    /**
     * @return \Generator<string, array{\Closure(): SalesOrderSearch, string}>
     */
    public static function invalidSearches(): \Generator
    {
        yield 'id' => [static fn (): SalesOrderSearch => new SalesOrderSearch(id: 0), 'The id must be a positive integer'];
        yield 'order id' => [static fn (): SalesOrderSearch => new SalesOrderSearch(orderId: ''), 'The order id must be a non-empty string'];
        yield 'reference' => [static fn (): SalesOrderSearch => new SalesOrderSearch(reference: ''), 'The reference must be a non-empty string'];
        yield 'bill-to email' => [static fn (): SalesOrderSearch => new SalesOrderSearch(billToEmail: ''), 'The bill-to email must be a non-empty string'];
        yield 'bill-to mobile' => [static fn (): SalesOrderSearch => new SalesOrderSearch(billToMobile: ''), 'The bill-to mobile must be a non-empty string'];
        yield 'ship-to email' => [static fn (): SalesOrderSearch => new SalesOrderSearch(shipToEmail: ''), 'The ship-to email must be a non-empty string'];
        yield 'ship-to mobile' => [static fn (): SalesOrderSearch => new SalesOrderSearch(shipToMobile: ''), 'The ship-to mobile must be a non-empty string'];
        yield 'created range' => [
            static fn (): SalesOrderSearch => new SalesOrderSearch(createdAtMin: new \DateTimeImmutable('2026-11-07T11:00:00+01:00'), createdAtMax: new \DateTimeImmutable('2026-11-07T10:00:00+01:00')),
            'The earliest created time must not be after the latest created time',
        ];
        yield 'updated range' => [
            static fn (): SalesOrderSearch => new SalesOrderSearch(updatedAtMin: new \DateTimeImmutable('2026-11-07T11:00:00+01:00'), updatedAtMax: new \DateTimeImmutable('2026-11-07T10:00:00+01:00')),
            'The earliest updated time must not be after the latest updated time',
        ];
    }

    #[Test]
    public function it_accepts_the_first_id(): void
    {
        self::assertSame(1, (new SalesOrderSearch(id: 1))->toArray()['id']);
    }

    #[Test]
    public function it_accepts_a_range_of_one_instant(): void
    {
        $time = new \DateTimeImmutable('2026-11-07T10:00:00+01:00');

        self::assertSame('2026-11-07T10:00:00.000+01:00', (new SalesOrderSearch(createdAtMin: $time, createdAtMax: $time))->toArray()['created_at_max']);
        self::assertSame('2026-11-07T10:00:00.000+01:00', (new SalesOrderSearch(updatedAtMin: $time, updatedAtMax: $time))->toArray()['updated_at_max']);
    }
}
