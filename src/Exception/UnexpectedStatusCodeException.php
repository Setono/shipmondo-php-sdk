<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown for a status code that is neither a success (2xx) nor an error (4xx or 5xx), e.g. a redirect the HTTP client
 * didn't follow.
 */
final class UnexpectedStatusCodeException extends ResponseAwareException
{
}
