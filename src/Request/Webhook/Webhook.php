<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Webhook;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Request\Payload;
use Webmozart\Assert\Assert;

/**
 * Typed request body for `POST /webhooks`. The `endpoint` must be an HTTPS URL; Shipmondo tests it
 * immediately on creation and expects an HTTP 200 response. `key` is the encryption key used to
 * sign the webhook payloads.
 */
final class Webhook extends Payload
{
    public function __construct(
        public readonly string $name,
        public readonly string $endpoint,
        public readonly string $key,
        public readonly WebhookAction $action,
        public readonly WebhookResourceName $resourceName,
    ) {
        Assert::stringNotEmpty($name);
        Assert::stringNotEmpty($endpoint);
        Assert::stringNotEmpty($key);
    }
}
