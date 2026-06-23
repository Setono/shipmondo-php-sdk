<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\Webhook;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Response\Resource;

/**
 * `action` and `resourceName` are typed enums: a webhook whose action/resource the SDK does not
 * model fails to map (a {@see \Setono\Shipmondo\Exception\MappingException}) rather than silently
 * passing an unknown value through. The original string values remain available via {@see Resource::$raw}.
 */
final class Webhook extends Resource
{
    public function __construct(
        public readonly int $id,
        public readonly string $endpoint,
        public readonly bool $active,
        public readonly string $name,
        public readonly WebhookAction $action,
        public readonly WebhookResourceName $resourceName,
    ) {
    }
}
