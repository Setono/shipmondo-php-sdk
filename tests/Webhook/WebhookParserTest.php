<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Webhook;

use Firebase\JWT\JWT;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Exception\MalformedWebhookException;
use Setono\Shipmondo\Exception\WebhookException;
use Setono\Shipmondo\Exception\WebhookVerificationException;

#[CoversClass(WebhookParser::class)]
#[CoversClass(WebhookEvent::class)]
final class WebhookParserTest extends TestCase
{
    // HS256 (and the HS384 case below) require keys of at least 32/48 bytes; keep these comfortably long.
    private const KEY = '0123456789abcdef0123456789abcdef0123456789abcdef';

    private const OTHER_KEY = 'fedcba9876543210fedcba9876543210fedcba9876543210';

    #[Test]
    public function it_verifies_and_parses_a_webhook_from_a_psr7_request(): void
    {
        $body = self::body(self::KEY, [
            'webhook' => 'My Webhook',
            'data' => ['id' => 123, 'status' => 'created', 'sender' => ['name' => 'ACME']],
            'url' => 'https://example.com/webhook',
        ]);

        $request = new ServerRequest('POST', 'https://example.com/webhook', [
            'SMD-Resource-Type' => 'Shipments',
            'SMD-Resource-Id' => '123',
            'SMD-Webhook-Id' => '456',
            'SMD-Action' => 'create',
            'SMD-User' => 'user@example.com',
            'Content-Type' => 'application/json',
        ], $body);

        $event = (new WebhookParser())->parse($request, self::KEY);

        self::assertSame(WebhookAction::Create, $event->action);
        self::assertSame(WebhookResourceName::Shipments, $event->resourceType);
        self::assertSame(123, $event->resourceId);
        self::assertSame(456, $event->webhookId);
        self::assertSame('user@example.com', $event->user);
        self::assertSame('My Webhook', $event->webhookName);
        self::assertSame('https://example.com/webhook', $event->url);
        self::assertSame('created', $event->data['status']);

        $sender = $event->data['sender'];
        self::assertIsArray($sender);
        self::assertSame('ACME', $sender['name']);
    }

    #[Test]
    public function it_parses_from_a_raw_body_and_headers_with_case_insensitive_lookup(): void
    {
        $body = self::body(self::KEY, [
            'webhook' => 'wh',
            'data' => ['id' => 7],
            'url' => 'https://example.com/webhook',
        ]);

        $event = (new WebhookParser())->parsePayload($body, [
            'smd-action' => 'cancel',
            'SMD-RESOURCE-TYPE' => 'Orders',
            'Smd-Resource-Id' => '7',
        ], self::KEY);

        self::assertSame(WebhookAction::Cancel, $event->action);
        self::assertSame(WebhookResourceName::Orders, $event->resourceType);
        self::assertSame(7, $event->resourceId);
        // Optional headers that were not sent come back as null rather than throwing.
        self::assertNull($event->webhookId);
        self::assertNull($event->user);
    }

    #[Test]
    public function it_throws_when_the_action_header_is_unknown(): void
    {
        $body = self::body(self::KEY, ['webhook' => 'wh', 'data' => [], 'url' => 'https://example.com/webhook']);

        $this->expectException(MalformedWebhookException::class);

        (new WebhookParser())->parsePayload($body, ['SMD-Action' => 'teleport', 'SMD-Resource-Type' => 'Orders'], self::KEY);
    }

    #[Test]
    public function it_throws_when_the_action_header_is_missing(): void
    {
        $body = self::body(self::KEY, ['webhook' => 'wh', 'data' => [], 'url' => 'https://example.com/webhook']);

        $this->expectException(MalformedWebhookException::class);

        // No SMD-Action header at all — a delivery the SDK can't interpret, so it blows up.
        (new WebhookParser())->parsePayload($body, ['SMD-Resource-Type' => 'Orders'], self::KEY);
    }

    #[Test]
    public function it_throws_when_the_resource_type_header_is_unknown(): void
    {
        $body = self::body(self::KEY, ['webhook' => 'wh', 'data' => [], 'url' => 'https://example.com/webhook']);

        $this->expectException(MalformedWebhookException::class);

        (new WebhookParser())->parsePayload($body, ['SMD-Action' => 'create', 'SMD-Resource-Type' => 'Galaxies'], self::KEY);
    }

    #[Test]
    public function it_throws_a_verification_exception_when_the_signature_does_not_verify(): void
    {
        $body = self::body(self::KEY, [
            'webhook' => 'wh',
            'data' => ['id' => 1],
            'url' => 'https://example.com/webhook',
        ]);

        $this->expectException(WebhookVerificationException::class);

        (new WebhookParser())->parsePayload($body, [], self::OTHER_KEY);
    }

    #[Test]
    public function the_verification_exception_is_catchable_as_a_webhook_exception(): void
    {
        $body = self::body(self::KEY, ['webhook' => 'wh', 'data' => [], 'url' => 'https://example.com/webhook']);

        $this->expectException(WebhookException::class);

        (new WebhookParser())->parsePayload($body, [], self::OTHER_KEY);
    }

    #[Test]
    public function it_rejects_a_key_that_is_too_short_to_verify(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WebhookParser())->parsePayload('{"data":"x"}', [], 'too-short');
    }

    #[Test]
    public function it_throws_a_malformed_exception_when_the_body_is_not_json(): void
    {
        $this->expectException(MalformedWebhookException::class);

        (new WebhookParser())->parsePayload('this is not json', [], self::KEY);
    }

    #[Test]
    public function it_throws_a_malformed_exception_when_the_data_field_is_missing(): void
    {
        $this->expectException(MalformedWebhookException::class);

        (new WebhookParser())->parsePayload('{"foo":"bar"}', [], self::KEY);
    }

    #[Test]
    public function it_throws_a_malformed_exception_when_the_token_is_not_a_jwt(): void
    {
        $this->expectException(MalformedWebhookException::class);

        (new WebhookParser())->parsePayload('{"data":"not-a-jwt"}', [], self::KEY);
    }

    #[Test]
    public function it_rejects_a_token_signed_with_a_different_algorithm(): void
    {
        // Signed with HS384 — must be rejected because the parser pins HS256 (algorithm confusion).
        $jwt = JWT::encode(['webhook' => 'wh', 'data' => [], 'url' => 'https://example.com/webhook'], self::KEY, 'HS384');
        $body = json_encode(['data' => $jwt], \JSON_THROW_ON_ERROR);

        $this->expectException(MalformedWebhookException::class);

        (new WebhookParser())->parsePayload($body, [], self::KEY);
    }

    /**
     * Builds a Shipmondo-shaped webhook body: a JWT (HS256, signed with $key) wrapped in
     * `{"data": "<jwt>"}`.
     *
     * @param array<string, mixed> $payload
     */
    private static function body(string $key, array $payload): string
    {
        $jwt = JWT::encode($payload, $key, 'HS256');

        return json_encode(['data' => $jwt], \JSON_THROW_ON_ERROR);
    }
}
