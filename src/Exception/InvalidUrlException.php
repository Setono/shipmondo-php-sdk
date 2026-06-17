<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown when a URL passed to the low-level client helpers is rejected — e.g. an absolute URL
 * pointing at a host other than the configured Shipmondo host, an explicit non-default port, or a
 * query argument combined with an absolute/query-bearing URL.
 */
final class InvalidUrlException extends \InvalidArgumentException implements ShipmondoException
{
}
