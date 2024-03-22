<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Response;

/**
 * @mixin Endpoint
 *
 * @template TResponse of Response
 */
trait RemovableEndpointTrait
{
    /**
     * @return TResponse
     */
    public function remove(int $id): Response
    {
        return $this
            ->mapperBuilder
            ->mapper()
            ->map(
                self::getResponseClass(),
                $this->createSource(
                    $this->client->delete($this->endpoint, $id),
                ),
            );
    }
}
