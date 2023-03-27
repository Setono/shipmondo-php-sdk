<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

use Psr\Http\Message\ResponseInterface;

final class NotAuthorizedException extends ResponseAwareException
{
    public static function assert(ResponseInterface $response): void
    {
        if ($response->getStatusCode() === 401) {
            throw new self($response);
        }
    }
}
