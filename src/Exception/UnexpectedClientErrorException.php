<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown for a 4xx Client Error response that does not map to a more specific exception, e.g. 400 Bad Request or
 * 409 Conflict.
 */
final class UnexpectedClientErrorException extends ClientErrorException
{
}
