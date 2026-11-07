<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SalesOrderSearch::class)]
final class SalesOrderSearchTest extends TestCase
{
    #[Test]
    public function it_sends_every_filter_by_its_query_name(): void
    {
        $search = new SalesOrderSearch(
            orderId: '000001234',
            reference: 'REF-1',
            billToEmail: 'bill@example.com',
            billToMobile: '+4511111111',
            shipToEmail: 'ship@example.com',
            shipToMobile: '+4522222222',
            archived: false,
            createdAtMin: new \DateTimeImmutable('2026-11-07T10:00:00+01:00'),
            createdAtMax: new \DateTimeImmutable('2026-11-07T11:00:00+01:00'),
            updatedAtMin: new \DateTimeImmutable('2026-11-07T12:00:00+01:00'),
            updatedAtMax: new \DateTimeImmutable('2026-11-07T13:00:00+01:00'),
        );

        self::assertSame([
            'order_id' => '000001234',
            'reference' => 'REF-1',
            'bill_to_email' => 'bill@example.com',
            'bill_to_mobile' => '+4511111111',
            'ship_to_email' => 'ship@example.com',
            'ship_to_mobile' => '+4522222222',
            'archived' => 'false',
            'created_at_min' => '2026-11-07T10:00:00+01:00',
            'created_at_max' => '2026-11-07T11:00:00+01:00',
            'updated_at_min' => '2026-11-07T12:00:00+01:00',
            'updated_at_max' => '2026-11-07T13:00:00+01:00',
        ], $search->toArray());
    }

    #[Test]
    public function it_leaves_out_the_filters_it_is_not_given(): void
    {
        self::assertSame(['order_id' => '000001234'], array_filter((new SalesOrderSearch(orderId: '000001234'))->toArray(), static fn (mixed $value): bool => null !== $value));
        self::assertSame('true', (new SalesOrderSearch(archived: true))->toArray()['archived']);
    }

    #[Test]
    public function it_refuses_an_empty_text_filter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SalesOrderSearch(orderId: '');
    }
}
