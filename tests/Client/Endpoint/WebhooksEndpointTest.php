<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Client\Client;
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
        self::assertSame(WebhookAction::Create, $webhook->action);
        self::assertSame(WebhookResourceName::Shipments, $webhook->resourceName);

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

        try {
            $this->client($http)->webhooks()->create(new WebhookRequest(
                name: 'sdk-test',
                endpoint: 'https://example.com',
                key: 'a-sufficiently-long-webhook-signing-key',
                action: WebhookAction::Create,
                resourceName: WebhookResourceName::Shipments,
            ));
            self::fail('Expected a MappingException');
        } catch (MappingException $e) {
            // The message names the target DTO and the request that produced the unmappable body.
            self::assertStringStartsWith(
                sprintf('Could not map response body to %s [POST %s/webhooks]: ', Webhook::class, self::BASE),
                $e->getMessage(),
            );
            self::assertInstanceOf(MappingError::class, $e->getPrevious());
            self::assertSame(200, $e->getResponse()->getStatusCode());
        }
    }

    /**
     * Shipmondo lists every webhook on the account, also other integrations' and ones made in its UI, so a webhook
     * whose action or resource the SDK doesn't model must not make the page unreadable
     */
    #[Test]
    public function it_reads_a_webhook_whose_action_or_resource_the_sdk_does_not_know_with_null_in_its_place(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/webhooks?page=1&per_page=20',
            '[{"id":1,"endpoint":"https://a","active":true,"name":"known","action":"delete","resource_name":"Orders"},'
            . '{"id":2,"endpoint":"https://b","active":true,"name":"unknown action","action":"an_action_added_later","resource_name":"Orders"},'
            . '{"id":3,"endpoint":"https://c","active":false,"name":"unknown resource","action":"create","resource_name":"A resource added later"}]',
            200,
            ['X-Total-Pages' => '1'],
        );

        $webhooks = $this->client($http)->webhooks()->getPage()->items;

        self::assertSame(
            [[1, WebhookAction::Delete, WebhookResourceName::Orders], [2, null, WebhookResourceName::Orders], [3, WebhookAction::Create, null]],
            array_map(static fn (Webhook $webhook): array => [$webhook->id, $webhook->action, $webhook->resourceName], $webhooks),
        );
        // the original strings stay reachable
        self::assertSame('an_action_added_later', $webhooks[1]->raw['action']);
        self::assertSame('A resource added later', $webhooks[2]->raw['resource_name']);
        self::assertSame(['https://c', false, 'unknown resource'], [$webhooks[2]->endpoint, $webhooks[2]->active, $webhooks[2]->name]);
    }

    /**
     * The leniency lives on the webhook's fields, not in the SDK's mapper configuration, so it also holds for a consumer
     * that passes a mapper builder of its own without {@see Client::configureMapperBuilder()}
     */
    #[Test]
    public function it_reads_an_unknown_action_as_null_also_with_a_mapper_builder_of_the_consumers_own(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/webhooks?page=1&per_page=20',
            '[{"id":2,"endpoint":"https://b","active":true,"name":"unknown action","action":"an_action_added_later","resource_name":"Orders"}]',
            200,
            ['X-Total-Pages' => '1'],
        );
        $psr17 = new Psr17Factory();
        $client = new Client('user', 'key', sandbox: true, httpClient: $http, requestFactory: $psr17, streamFactory: $psr17, mapperBuilder: new MapperBuilder());

        $webhook = $client->webhooks()->getPage()->items[0];

        self::assertNull($webhook->action);
        self::assertSame(WebhookResourceName::Orders, $webhook->resourceName);
    }

    #[Test]
    public function it_paginates_and_deletes_past_a_webhook_whose_action_the_sdk_does_not_know(): void
    {
        $http = (new ScriptedHttpClient())
            ->on(
                self::BASE . '/webhooks?page=1&per_page=20',
                '[{"id":1,"endpoint":"https://a","active":true,"name":"another integration","action":"an_action_added_later","resource_name":"Orders"},'
                . '{"id":2,"endpoint":"https://b","active":true,"name":"drop","action":"create","resource_name":"Orders"}]',
                200,
                ['X-Total-Pages' => '1'],
            )
            ->on(self::BASE . '/webhooks/2', '')
        ;

        $webhooks = $this->client($http)->webhooks();
        self::assertSame([1, 2], array_map(static fn (Webhook $webhook): int => $webhook->id, iterator_to_array($webhooks->paginate(), false)));

        $webhooks->deleteAll(static fn (Webhook $webhook): bool => 'drop' === $webhook->name);

        self::assertSame(['GET', 'GET', 'DELETE'], array_map(static fn ($request): string => $request->getMethod(), $http->sentRequests));
    }

    /**
     * Only the action and the resource are read leniently: a webhook without what identifies it, or with an action that
     * isn't even a string, still fails to map
     *
     * @param array<string, mixed> $webhook
     */
    #[Test]
    #[DataProvider('unreadableWebhooks')]
    public function it_still_fails_to_map_a_webhook_it_cannot_read(array $webhook): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/webhooks?page=1&per_page=20',
            json_encode([$webhook], \JSON_THROW_ON_ERROR),
            200,
            ['X-Total-Pages' => '1'],
        );

        $this->expectException(MappingException::class);

        $this->client($http)->webhooks()->getPage();
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public static function unreadableWebhooks(): \Generator
    {
        $webhook = ['id' => 1, 'endpoint' => 'https://a', 'active' => true, 'name' => 'a', 'action' => 'an_action_added_later', 'resource_name' => 'A resource added later'];

        foreach (['id', 'endpoint', 'active', 'name'] as $field) {
            $without = $webhook;
            unset($without[$field]);

            yield sprintf('without %s', $field) => [$without];
        }

        yield 'an action that is not a string' => [['action' => ['delete']] + $webhook];
        yield 'a resource that is not a string' => [['resource_name' => ['Orders']] + $webhook];
    }

    #[Test]
    public function it_skips_non_array_rows_in_a_page(): void
    {
        // The bare array mixes valid webhooks with scalar rows; the scalars must be skipped (not
        // mapped, and without dropping the rows that follow them).
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/webhooks?page=1&per_page=20',
            '[5,{"id":1,"endpoint":"https://a","active":true,"name":"a","action":"create","resource_name":"Orders"},"x",'
            . '{"id":2,"endpoint":"https://b","active":true,"name":"b","action":"create","resource_name":"Orders"}]',
            200,
            ['X-Total-Pages' => '1'],
        );

        $page = $this->client($http)->webhooks()->getPage();

        self::assertSame([1, 2], array_map(static fn (Webhook $webhook): int => $webhook->id, $page->items));
    }

    #[Test]
    public function it_rejects_an_invalid_resource_action_combination_before_sending(): void
    {
        // "delete" is only valid for Orders, not Shipments — this must throw before any HTTP call.
        $http = new ScriptedHttpClient();

        try {
            $this->client($http)->webhooks()->create(new WebhookRequest(
                name: 'sdk-test',
                endpoint: 'https://example.com',
                key: 'a-sufficiently-long-webhook-signing-key',
                action: WebhookAction::Delete,
                resourceName: WebhookResourceName::Shipments,
            ));
            self::fail('Expected an InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(
                'The "delete" action is not valid for the "Shipments" resource. Valid actions are: create, cancel.',
                $e->getMessage(),
            );
        }

        self::assertSame([], $http->sentRequests);
    }

    #[Test]
    #[DataProvider('partialResourceActionPairs')]
    public function it_leaves_a_partial_resource_action_pair_to_the_server(?WebhookAction $action, ?WebhookResourceName $resourceName): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/webhooks', self::fixture('webhook.json'));

        $this->client($http)->webhooks()->create(new WebhookRequest(name: 'sdk-test', action: $action, resourceName: $resourceName));

        self::assertCount(1, $http->sentRequests);
    }

    /**
     * @return \Generator<string, array{?WebhookAction, ?WebhookResourceName}>
     */
    public static function partialResourceActionPairs(): \Generator
    {
        yield 'resource without an action' => [null, WebhookResourceName::Shipments];
        yield 'action without a resource' => [WebhookAction::Delete, null];
    }
}
