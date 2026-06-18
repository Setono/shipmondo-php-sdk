<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\Webhook;

use Setono\Shipmondo\Response\Resource;

/**
 * `action` and `resourceName` are kept as plain strings (not enums) on the read side so the SDK
 * never fails to map a response when Shipmondo introduces a new action/resource value. The raw
 * values are also available via {@see Resource::$raw}.
 */
final class Webhook extends Resource
{
    public function __construct(
        public readonly int $id,
        public readonly string $endpoint,
        public readonly bool $active,
        public readonly string $name,
        public readonly string $action,
        public readonly string $resourceName,
    ) {
    }
}
