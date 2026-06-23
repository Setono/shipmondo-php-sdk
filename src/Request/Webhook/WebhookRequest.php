<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Webhook;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Request\Payload;
use Setono\Shipmondo\Webhook\WebhookParser;

/**
 * Typed request body for `POST /webhooks`. All properties are optional and mutable; the API
 * enforces what's required. The `endpoint` must be an HTTPS URL — Shipmondo calls it immediately on
 * creation and expects an HTTP 200 response.
 *
 * `key` is the secret Shipmondo uses to HS256-sign every webhook delivery, and that you verify
 * incoming deliveries with via {@see WebhookParser}. Unlike the other (server-validated) fields it
 * is validated here: HS256 requires a key of at least {@see WebhookParser::MINIMUM_KEY_LENGTH} bytes
 * (RFC 7518 §3.2), so creating a webhook with a shorter key — which the SDK could then never verify
 * — is rejected up front.
 */
final class WebhookRequest extends Payload
{
    /**
     * @throws \InvalidArgumentException if $key is shorter than {@see WebhookParser::MINIMUM_KEY_LENGTH} bytes
     */
    public function __construct(
        public ?string $name = null,
        public ?string $endpoint = null,
        public ?string $key = null,
        public ?WebhookAction $action = null,
        public ?WebhookResourceName $resourceName = null,
    ) {
        if (null !== $key && strlen($key) < WebhookParser::MINIMUM_KEY_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'The webhook key must be at least %d bytes long so it can HS256-sign deliveries (RFC 7518 '
                . 'requires an HS256 key of at least 256 bits), got %d.',
                WebhookParser::MINIMUM_KEY_LENGTH,
                strlen($key),
            ));
        }
    }
}
