<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Response\Collection;
use Setono\Shipmondo\Response\Response;

/**
 * @template TResponse of Response
 */
interface EndpointInterface
{
    /**
     * @return Collection<TResponse>
     */
    public function get(CollectionQuery $query = null): Collection;
}
