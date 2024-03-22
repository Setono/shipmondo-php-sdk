<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\SalesOrders;

use Setono\Shipmondo\Response\Response;

final class SalesOrder extends Response
{
    public function __construct(
        public readonly int $id,
    ) {
    }
}
