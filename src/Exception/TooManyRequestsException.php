<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown for 429 Too Many Requests responses — the API rate limit was exceeded.
 */
final class TooManyRequestsException extends ClientErrorException
{
}
