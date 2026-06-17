<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
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
}
