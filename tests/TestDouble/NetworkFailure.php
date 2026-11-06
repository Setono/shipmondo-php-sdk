<?php

declare(strict_types=1);

namespace Setono\Shipmondo\TestDouble;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * What a PSR-18 HTTP client throws when it can't reach the server, e.g. a refused connection or a timeout
 */
final class NetworkFailure extends \RuntimeException implements NetworkExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, string $message)
    {
        parent::__construct($message);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
