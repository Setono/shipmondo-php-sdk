<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Webhook;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Request\Payload;

/**
 * Typed request body for `POST /webhooks`. All properties are optional and mutable; the API
 * enforces what's required. The `endpoint` must be an HTTPS URL — Shipmondo calls it immediately on
 * creation and expects an HTTP 200 response. `key` is the encryption key used to sign the payloads.
 */
final class WebhookRequest extends Payload
{
    public function __construct(
        public ?string $name = null,
        public ?string $endpoint = null,
        public ?string $key = null,
        public ?WebhookAction $action = null,
        public ?WebhookResourceName $resourceName = null,
    ) {
    }
}
