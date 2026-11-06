<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Thrown when the PSR-18 HTTP client fails, so there is no response from Shipmondo: e.g. Shipmondo couldn't be
 * reached, the request timed out, or the request couldn't be sent. The HTTP client's exception is the previous
 * exception.
 *
 * It is a PSR-18 {@see ClientExceptionInterface} as well, so code that catches the HTTP client's exceptions still
 * catches it. Like the message of a {@see ResponseAwareException}, the message leaves out the query string.
 */
final class ConnectionException extends \RuntimeException implements ShipmondoException, ClientExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, ClientExceptionInterface $previous)
    {
        parent::__construct(sprintf(
            'Could not get a response from Shipmondo [%s %s]. The HTTP client\'s exception is the previous exception',
            $request->getMethod(),
            $request->getUri()->withQuery('')->withFragment(''),
        ), previous: $previous);
    }

    /**
     * The request the HTTP client failed to send, or to get a response to
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
