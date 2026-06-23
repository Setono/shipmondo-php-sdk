<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Webhook;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ServerRequestInterface;
use Setono\Shipmondo\Exception\MalformedWebhookException;
use Setono\Shipmondo\Exception\WebhookVerificationException;

/**
 * Verifies and parses incoming Shipmondo webhooks.
 *
 * Shipmondo POSTs a body shaped `{"data": "<JWT>"}`, where the JWT is HS256-signed with the `key`
 * you set on the webhook, plus five `SMD-*` headers carrying metadata. This parser verifies the
 * signature — the only proof the request genuinely came from Shipmondo — and returns a typed
 * {@see WebhookEvent}.
 *
 * Verification pins the HS256 algorithm, so a token presenting any other `alg` (including the
 * `none` algorithm) is rejected, closing the classic JWT algorithm-confusion hole.
 *
 * The parser is stateless and safe to reuse or register as a service. The webhook `key` is passed
 * per call, so a server hosting several webhooks can pick the right key (e.g. by the
 * `SMD-Webhook-Id` header) before verifying.
 *
 * ```php
 * $event = (new WebhookParser())->parse($serverRequest, $webhookKey);
 *
 * $event->action;       // 'create'
 * $event->resourceType; // 'Shipments'
 * $event->data;         // array<array-key, mixed> — the resource, snake_case as in the API docs
 * ```
 */
final class WebhookParser implements WebhookParserInterface
{
    /**
     * The minimum webhook key length, in bytes. RFC 7518 §3.2 requires an HS256 key of at least the
     * hash size — 256 bits / 32 bytes — and firebase/php-jwt enforces exactly that on verification.
     * The SDK applies the same floor when creating webhooks (see {@see \Setono\Shipmondo\Request\Webhook\WebhookRequest}).
     */
    public const MINIMUM_KEY_LENGTH = 32;

    private const ALGORITHM = 'HS256';

    /** @var list<string> the five metadata headers Shipmondo sends with every delivery */
    private const HEADERS = [
        'SMD-Resource-Type',
        'SMD-Resource-Id',
        'SMD-Webhook-Id',
        'SMD-Action',
        'SMD-User',
    ];

    /**
     * Verify and parse a webhook delivered as a PSR-7 server request.
     *
     * @throws WebhookVerificationException if the signature does not verify with $key (forged request / wrong key)
     * @throws MalformedWebhookException if the body or token is structurally invalid
     */
    public function parse(ServerRequestInterface $request, string $key): WebhookEvent
    {
        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $headers = [];
        foreach (self::HEADERS as $name) {
            $headers[$name] = $request->getHeaderLine($name);
        }

        return $this->parsePayload((string) $body, $headers, $key);
    }

    /**
     * Verify and parse a webhook from its raw request body and headers — for consumers not using
     * PSR-7 (e.g. native Laravel/Symfony requests).
     *
     * @param array<string, string> $headers the request headers, looked up case-insensitively
     *
     * @throws \InvalidArgumentException if $key is shorter than {@see self::MINIMUM_KEY_LENGTH} bytes (HS256 cannot verify it)
     * @throws WebhookVerificationException if the signature does not verify with $key (forged request / wrong key)
     * @throws MalformedWebhookException if the body or token is structurally invalid
     */
    public function parsePayload(string $body, array $headers, string $key): WebhookEvent
    {
        if (strlen($key) < self::MINIMUM_KEY_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'The webhook key must be at least %d bytes long for HS256 verification, got %d. Shipmondo '
                . 'signs webhooks with the key you set when creating them — pick a key of at least %d bytes.',
                self::MINIMUM_KEY_LENGTH,
                strlen($key),
                self::MINIMUM_KEY_LENGTH,
            ));
        }

        $token = self::extractToken($body);

        try {
            $decoded = JWT::decode($token, new Key($key, self::ALGORITHM));
        } catch (SignatureInvalidException $e) {
            throw new WebhookVerificationException(
                'The webhook signature could not be verified with the supplied key.',
                previous: $e,
            );
        } catch (\UnexpectedValueException | \DomainException $e) {
            // Malformed token, wrong number of segments, or an algorithm other than HS256.
            throw new MalformedWebhookException(
                sprintf('The webhook token could not be decoded: %s', $e->getMessage()),
                previous: $e,
            );
        }

        $payload = self::normalizePayload($decoded);
        $headers = self::lowercaseKeys($headers);

        $user = $headers['smd-user'] ?? '';

        return new WebhookEvent(
            action: $headers['smd-action'] ?? '',
            resourceType: $headers['smd-resource-type'] ?? '',
            resourceId: self::toInt($headers['smd-resource-id'] ?? ''),
            webhookId: self::toInt($headers['smd-webhook-id'] ?? ''),
            user: '' === $user ? null : $user,
            webhookName: self::stringClaim($payload, 'webhook'),
            url: self::stringClaim($payload, 'url'),
            data: self::arrayClaim($payload, 'data'),
        );
    }

    /**
     * Decode the `{"data": "<JWT>"}` envelope and return the JWT string.
     *
     * @throws MalformedWebhookException
     */
    private static function extractToken(string $body): string
    {
        try {
            $envelope = json_decode($body, true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MalformedWebhookException(
                sprintf('The webhook body is not valid JSON: %s.', $e->getMessage()),
                previous: $e,
            );
        }

        if (!is_array($envelope) || !isset($envelope['data']) || !is_string($envelope['data'])) {
            throw new MalformedWebhookException('The webhook body must be a JSON object with a string "data" field.');
        }

        return $envelope['data'];
    }

    /**
     * firebase/php-jwt returns the claims as nested `stdClass` objects; convert to a deep array so
     * the nested resource (`data`) is reachable as a plain array (matching {@see \Setono\Shipmondo\Response\Resource::$raw}).
     *
     * @return array<array-key, mixed>
     *
     * @throws MalformedWebhookException
     */
    private static function normalizePayload(object $decoded): array
    {
        try {
            $payload = json_decode(json_encode($decoded, \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MalformedWebhookException(
                sprintf('The webhook payload could not be normalized: %s.', $e->getMessage()),
                previous: $e,
            );
        }

        if (!is_array($payload)) {
            throw new MalformedWebhookException('The webhook payload did not decode to a JSON object.');
        }

        return $payload;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private static function lowercaseKeys(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $value) {
            $result[strtolower($name)] = $value;
        }

        return $result;
    }

    private static function toInt(string $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private static function stringClaim(array $payload, string $claim): string
    {
        $value = $payload[$claim] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    private static function arrayClaim(array $payload, string $claim): array
    {
        $value = $payload[$claim] ?? null;

        return is_array($value) ? $value : [];
    }
}
