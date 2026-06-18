<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown for 401 Unauthorized responses — typically an invalid API user or API key.
 */
final class UnauthorizedException extends ClientErrorException
{
}
