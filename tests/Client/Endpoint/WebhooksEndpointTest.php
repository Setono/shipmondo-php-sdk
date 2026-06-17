<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;
use Setono\Shipmondo\Request\Webhook\Webhook as WebhookRequest;
use Setono\Shipmondo\Response\Webhook\Webhook as WebhookResponse;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class WebhooksEndpointTest extends ShipmondoTestCase
{
    #[Test]
    public function it_creates_a_webhook(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/webhooks',
            '{"id":5,"endpoint":"https://example.com/hook","active":true,"name":"w","action":"create","resource_name":"Orders"}',
        );

        $webhook = $this->client($http)->webhooks()->create(new WebhookRequest(
            name: 'w',
            endpoint: 'https://example.com/hook',
            key: 'secret',
            action: WebhookAction::Create,
            resourceName: WebhookResourceName::Orders,
        ));

        self::assertSame(5, $webhook->id);

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('create', $body['action']);
        self::assertSame('Orders', $body['resource_name']);
        self::assertSame('secret', $body['key']);
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
            static fn (WebhookResponse $webhook): bool => 'drop' === $webhook->name,
        );

        $deleted = array_values(array_filter(
            $http->sentRequests,
            static fn ($request): bool => 'DELETE' === $request->getMethod(),
        ));

        self::assertCount(1, $deleted);
        self::assertSame(self::BASE . '/webhooks/2', (string) $deleted[0]->getUri());
    }
}
