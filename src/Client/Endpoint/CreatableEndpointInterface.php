<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Request;
use Setono\Shipmondo\Response\Response;

/**
 * @template TResponse of Response
 */
interface CreatableEndpointInterface
{
    /**
     * @return TResponse
     */
    public function create(Request $request): Response;
}
