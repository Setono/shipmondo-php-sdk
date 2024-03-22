<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Response;

/**
 * @template TResponse of Response
 */
interface DeletableEndpointInterface
{
    /**
     * @return TResponse
     */
    public function delete(int $id): Response;
}
