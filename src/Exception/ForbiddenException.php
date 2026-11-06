<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown for 403 Forbidden responses — the API user is not allowed to make the request.
 */
final class ForbiddenException extends ClientErrorException
{
}
