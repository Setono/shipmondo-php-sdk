<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Request\CollectionRequestOptions;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class PaginationTest extends ShipmondoTestCase
{
    #[Test]
    public function it_walks_all_pages_until_the_last_one(): void
    {
        $http = (new ScriptedHttpClient())
            ->on(self::BASE . '/sales_orders?page=1&per_page=20', '[{"id":1},{"id":2}]', 200, ['X-Total-Pages' => '2'])
            ->on(self::BASE . '/sales_orders?page=2&per_page=20', '[{"id":3}]', 200, ['X-Current-Page' => '2', 'X-Total-Pages' => '2'])
        ;

        $ids = [];
        foreach ($this->client($http)->salesOrders()->paginate() as $salesOrder) {
            $ids[] = $salesOrder->id;
        }

        self::assertSame([1, 2, 3], $ids);
        self::assertCount(2, $http->sentRequests, 'paginate() should stop after the last page, not fetch an empty page');
    }

    #[Test]
    public function it_stops_on_an_empty_page(): void
    {
        // X-Total-Pages is deliberately larger than the real number of pages, so the stop must
        // come from the empty-page guard, not the total-pages guard.
        $http = (new ScriptedHttpClient())
            ->on(self::BASE . '/sales_orders?page=1&per_page=20', '[{"id":1}]', 200, ['X-Total-Pages' => '9'])
            ->on(self::BASE . '/sales_orders?page=2&per_page=20', '[]', 200, ['X-Total-Pages' => '9'])
        ;

        $ids = [];
        foreach ($this->client($http)->salesOrders()->paginate() as $salesOrder) {
            $ids[] = $salesOrder->id;
        }

        self::assertSame([1], $ids);
    }

    #[Test]
    public function it_walks_from_the_given_page_with_the_given_page_size(): void
    {
        $http = (new ScriptedHttpClient())
            ->on(self::BASE . '/sales_orders?page=3&per_page=2', '[{"id":5},{"id":6}]', 200, ['X-Total-Pages' => '4'])
            ->on(self::BASE . '/sales_orders?page=4&per_page=2', '[{"id":7}]', 200, ['X-Total-Pages' => '4'])
        ;

        $ids = [];
        foreach ($this->client($http)->salesOrders()->paginate(new CollectionRequestOptions(page: 3, perPage: 2)) as $salesOrder) {
            $ids[] = $salesOrder->id;
        }

        self::assertSame([5, 6, 7], $ids);
        self::assertSame(
            [self::BASE . '/sales_orders?page=3&per_page=2', self::BASE . '/sales_orders?page=4&per_page=2'],
            array_map(static fn ($request): string => (string) $request->getUri(), $http->sentRequests),
        );
    }

    #[Test]
    public function it_walks_until_an_empty_page_when_the_total_page_count_is_zero(): void
    {
        // X-Total-Pages: 0 carries no information, so only an empty page can end the walk.
        $http = (new ScriptedHttpClient())
            ->on(self::BASE . '/sales_orders?page=1&per_page=20', '[{"id":1}]', 200, ['X-Total-Pages' => '0'])
            ->on(self::BASE . '/sales_orders?page=2&per_page=20', '[{"id":2}]', 200, ['X-Total-Pages' => '0'])
            ->on(self::BASE . '/sales_orders?page=3&per_page=20', '[]', 200, ['X-Total-Pages' => '0'])
        ;

        $ids = [];
        foreach ($this->client($http)->salesOrders()->paginate() as $salesOrder) {
            $ids[] = $salesOrder->id;
        }

        self::assertSame([1, 2], $ids);
        self::assertCount(3, $http->sentRequests);
    }

    #[Test]
    public function it_falls_back_to_single_page_defaults_without_pagination_headers(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders?page=1&per_page=20', '[{"id":1},{"id":2}]');

        $page = $this->client($http)->salesOrders()->getPage();

        self::assertSame(1, $page->page);
        self::assertSame(2, $page->pageSize);
        self::assertSame(2, $page->totalCount);
        self::assertSame(1, $page->totalPages);
    }
}
