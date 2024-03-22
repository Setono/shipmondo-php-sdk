<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Response\Collection;

/**
 * @template TResponse
 */
interface EndpointInterface
{
    /**
     * @return Collection<TResponse>
     */
    public function get(CollectionQuery $query = null): Collection;
}
