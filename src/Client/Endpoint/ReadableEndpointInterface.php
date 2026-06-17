<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Response;

/**
 * @template TResponse of Response
 */
interface ReadableEndpointInterface
{
    /**
     * @return TResponse
     */
    public function getById(int $id): Response;
}
