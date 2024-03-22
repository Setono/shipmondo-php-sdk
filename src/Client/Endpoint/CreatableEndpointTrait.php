<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Request;
use Setono\Shipmondo\Response\Response;

/**
 * @mixin Endpoint
 *
 * @template TResponse of Response
 */
trait CreatableEndpointTrait
{
    /**
     * @return TResponse
     */
    public function create(Request $request): Response
    {
        return $this
            ->mapperBuilder
            ->mapper()
            ->map(
                self::getResponseClass(),
                $this->createSource(
                    $this->client->post($this->endpoint, $request),
                ),
            );
    }
}
