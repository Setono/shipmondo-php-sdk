<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Marker interface implemented by every exception thrown by this SDK.
 *
 * Consumers can write `catch (ShipmondoException $e) { ... }` to net all SDK-thrown exceptions
 * without catching `\Throwable` or maintaining an exception list. That includes a failure of the
 * HTTP client, which the SDK wraps in a {@see ConnectionException}.
 */
interface ShipmondoException extends \Throwable
{
}
