<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\Collection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    #[Test]
    public function it_exposes_items_and_pagination_metadata(): void
    {
        $collection = new Collection([1, 2, 3], page: 2, pageSize: 3, totalCount: 9, totalPages: 3);

        self::assertCount(3, $collection);
        self::assertFalse($collection->isEmpty());
        self::assertSame(1, $collection->first());
        self::assertSame([1, 2, 3], iterator_to_array($collection));
        self::assertSame(2, $collection->page);
        self::assertSame(3, $collection->pageSize);
        self::assertSame(9, $collection->totalCount);
        self::assertSame(3, $collection->totalPages);
    }

    #[Test]
    public function it_is_empty_and_uses_pagination_defaults_without_items(): void
    {
        $collection = new Collection([]);

        self::assertTrue($collection->isEmpty());
        self::assertCount(0, $collection);
        self::assertSame(1, $collection->page);
        self::assertSame(0, $collection->pageSize);
        self::assertSame(0, $collection->totalCount);
        self::assertSame(1, $collection->totalPages);
    }

    #[Test]
    public function it_filters_items_and_reindexes_while_preserving_pagination(): void
    {
        $collection = new Collection([1, 2, 3, 4], page: 2, pageSize: 4, totalCount: 8, totalPages: 2);

        $filtered = $collection->filter(static fn (int $n): bool => 0 === $n % 2);

        self::assertNotSame($collection, $filtered);
        self::assertSame([2, 4], $filtered->items);
        self::assertSame(2, $filtered->first());
        // pagination metadata is carried over to the filtered collection
        self::assertSame(2, $filtered->page);
        self::assertSame(4, $filtered->pageSize);
        self::assertSame(8, $filtered->totalCount);
        self::assertSame(2, $filtered->totalPages);

        // filtering everything out yields an empty collection whose first() is null
        $none = $collection->filter(static fn (int $n): bool => $n > 100);
        self::assertTrue($none->isEmpty());
        self::assertNull($none->first());
    }
}
