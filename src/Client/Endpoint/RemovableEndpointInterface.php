<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Response;

/**
 * @template TResponse of Response
 */
interface RemovableEndpointInterface
{
    /**
     * @return TResponse
     */
    public function remove(int $id): Response;
}
