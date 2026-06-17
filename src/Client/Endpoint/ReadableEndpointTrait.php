<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Response;

/**
 * @mixin Endpoint
 *
 * @template TResponse of Response
 */
trait ReadableEndpointTrait
{
    /**
     * @return TResponse
     */
    public function getById(int $id): Response
    {
        return $this
            ->mapperBuilder
            ->mapper()
            ->map(
                self::getResponseClass(),
                $this->createSource(
                    $this->client->get(sprintf('%s/%d', $this->endpoint, $id)),
                ),
            );
    }
}
