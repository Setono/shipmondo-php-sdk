<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown for 422 Unprocessable Entity responses — invalid or missing parameters on a write
 * request. The Shipmondo error message is available via {@see ResponseAwareException::getError()}.
 */
final class ValidationException extends ClientErrorException
{
}
