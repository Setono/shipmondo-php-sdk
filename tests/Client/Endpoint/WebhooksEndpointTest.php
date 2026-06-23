<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Exception\MappingException;
use Setono\Shipmondo\Request\Webhook\WebhookRequest;
use Setono\Shipmondo\Response\Webhook\Webhook;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class WebhooksEndpointTest extends ShipmondoTestCase
{
    #[Test]
    public function it_creates_a_webhook(): void
    {
        // Real sandbox payload for POST /webhooks.
        $http = (new ScriptedHttpClient())->on(self::BASE . '/webhooks', self::fixture('webhook.json'));

        $key = 'a-sufficiently-long-webhook-signing-key';

        $webhook = $this->client($http)->webhooks()->create(new WebhookRequest(
            name: 'sdk-test',
            endpoint: 'https://postman-echo.com/post',
            key: $key,
            action: WebhookAction::Create,
            resourceName: WebhookResourceName::Shipments,
        ));

        self::assertSame(8500472, $webhook->id);
        self::assertTrue($webhook->active);
        self::assertSame('create', $webhook->action);
        self::assertSame('Shipments', $webhook->resourceName);

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('create', $body['action']);
        self::assertSame('Shipments', $body['resource_name']);
        self::assertSame($key, $body['key']);
    }

    #[Test]
    public function it_deletes_a_webhook_by_id(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/webhooks/5', '');

        $this->client($http)->webhooks()->delete(5);

        self::assertSame('DELETE', $http->sentRequests[0]->getMethod());
        self::assertSame(self::BASE . '/webhooks/5', (string) $http->sentRequests[0]->getUri());
    }

    #[Test]
    public function it_deletes_all_webhooks_matching_a_predicate(): void
    {
        $http = (new ScriptedHttpClient())
            ->on(
                self::BASE . '/webhooks?page=1&per_page=20',
                '[{"id":1,"endpoint":"https://a","active":true,"name":"keep","action":"create","resource_name":"Orders"},'
                . '{"id":2,"endpoint":"https://b","active":true,"name":"drop","action":"create","resource_name":"Orders"}]',
                200,
                ['X-Total-Pages' => '1'],
            )
            ->on(self::BASE . '/webhooks/2', '')
        ;

        $this->client($http)->webhooks()->deleteAll(
            static fn (Webhook $webhook): bool => 'drop' === $webhook->name,
        );

        $deleted = array_values(array_filter(
            $http->sentRequests,
            static fn ($request): bool => 'DELETE' === $request->getMethod(),
        ));

        self::assertCount(1, $deleted);
        self::assertSame(self::BASE . '/webhooks/2', (string) $deleted[0]->getUri());
    }

    #[Test]
    public function it_throws_a_mapping_exception_when_the_response_does_not_fit_the_dto(): void
    {
        // 200 OK with a JSON object that decodes fine but can't map to Webhook (required fields missing).
        $http = (new ScriptedHttpClient())->on(self::BASE . '/webhooks', '{"foo":"bar"}');

        $this->expectException(MappingException::class);

        $this->client($http)->webhooks()->create(new WebhookRequest(
            name: 'sdk-test',
            endpoint: 'https://example.com',
            key: 'a-sufficiently-long-webhook-signing-key',
            action: WebhookAction::Create,
            resourceName: WebhookResourceName::Shipments,
        ));
    }

    #[Test]
    public function it_skips_non_array_rows_in_a_page(): void
    {
        // The bare array mixes a valid webhook with a scalar row; the scalar must be skipped, not mapped.
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/webhooks?page=1&per_page=20',
            '[{"id":1,"endpoint":"https://a","active":true,"name":"a","action":"create","resource_name":"Orders"},5]',
            200,
            ['X-Total-Pages' => '1'],
        );

        $page = $this->client($http)->webhooks()->getPage();

        self::assertCount(1, iterator_to_array($page));
    }
}
