<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\Webhook;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Response\Resource;

/**
 * `action` and `resourceName` are typed enums, and null when Shipmondo lists a value the SDK does not
 * model: the webhook list holds every webhook on the account, also other integrations', so one unknown
 * value must not make the whole list unreadable. The original strings remain available via
 * {@see Resource::$raw}. Any other field that doesn't fit still fails to map (a
 * {@see \Setono\Shipmondo\Exception\MappingException}).
 */
final class Webhook extends Resource
{
    public function __construct(
        public readonly int $id,
        public readonly string $endpoint,
        public readonly bool $active,
        public readonly string $name,
        public readonly ?WebhookAction $action,
        public readonly ?WebhookResourceName $resourceName,
    ) {
    }
}
