<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Thrown when there is no whole response from Shipmondo:
 *
 * - the PSR-18 HTTP client failed, so there is no response: e.g. Shipmondo couldn't be reached or didn't answer in
 *   time, or the request couldn't be sent. The HTTP client's exception is the previous exception;
 * - or Shipmondo answered with a success, but reading the response's body failed, e.g. the connection dropped while
 *   Shipmondo was sending it, or it took longer than the HTTP client's time limit. {@see self::getResponse()} is the
 *   success, so Shipmondo did what was asked, e.g. created a sales order, but what it answered is lost. The body's
 *   exception (PSR-7's `\RuntimeException`) is the previous exception. When the body of an error status can't be
 *   read, the client throws the exception of the status instead, with an empty body.
 *
 * It is a PSR-18 {@see ClientExceptionInterface} as well, so code that catches the HTTP client's exceptions still
 * catches it. Like the message of a {@see ResponseAwareException}, the message leaves out the query string.
 */
final class ConnectionException extends \RuntimeException implements ShipmondoException, ClientExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        ClientExceptionInterface|\RuntimeException $previous,
        private readonly ?ResponseInterface $response = null,
    ) {
        $uri = $request->getUri()->withQuery('')->withFragment('');

        parent::__construct(null === $response ? sprintf(
            'Could not get a response from Shipmondo [%s %s]. The HTTP client\'s exception is the previous exception',
            $request->getMethod(),
            $uri,
        ) : sprintf(
            'Shipmondo answered %d, but its response could not be read in full [%s %s]. The body\'s exception is the previous exception',
            $response->getStatusCode(),
            $request->getMethod(),
            $uri,
        ), previous: $previous);
    }

    /**
     * The request the HTTP client failed to send, or to get a whole response to
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * The success Shipmondo answered with when reading its body failed: its status and headers, with an empty body.
     * Null when the HTTP client failed, so there is no response
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
