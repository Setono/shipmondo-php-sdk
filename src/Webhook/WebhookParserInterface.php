<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Webhook;

use Psr\Http\Message\ServerRequestInterface;
use Setono\Shipmondo\Exception\MalformedWebhookException;
use Setono\Shipmondo\Exception\WebhookVerificationException;

/**
 * Verifies and parses incoming Shipmondo webhooks. Type-hint this in your application so the
 * concrete {@see WebhookParser} can be swapped or mocked.
 */
interface WebhookParserInterface
{
    /**
     * Verify and parse a webhook delivered as a PSR-7 server request.
     *
     * @throws \InvalidArgumentException if $key is too short to verify an HS256 signature with
     * @throws WebhookVerificationException if the signature does not verify with $key (forged request / wrong key)
     * @throws MalformedWebhookException if the body or token is structurally invalid
     */
    public function parse(ServerRequestInterface $request, string $key): WebhookEvent;

    /**
     * Verify and parse a webhook from its raw request body and headers — for consumers not using
     * PSR-7 (e.g. native Laravel/Symfony requests).
     *
     * @param array<string, string> $headers the request headers, looked up case-insensitively
     *
     * @throws \InvalidArgumentException if $key is too short to verify an HS256 signature with
     * @throws WebhookVerificationException if the signature does not verify with $key (forged request / wrong key)
     * @throws MalformedWebhookException if the body or token is structurally invalid
     */
    public function parsePayload(string $body, array $headers, string $key): WebhookEvent;
}
