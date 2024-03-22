<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Response\Collection;

/**
 * @template T
 */
interface EndpointInterface
{
    /**
     * @return Collection<T>
     */
    public function get(CollectionQuery $query = null): Collection;
}
