<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\SalesOrder;

use Setono\Shipmondo\Response\Resource;

/**
 * Entry-point response DTO for a single sales order.
 *
 * Only `id` is typed today; every other field of the (large) sales order schema stays reachable
 * via {@see Resource::$raw}, which the endpoint stamps with the full decoded body after mapping.
 */
final class SalesOrder extends Resource
{
    public function __construct(
        public readonly int $id,
    ) {
    }
}
