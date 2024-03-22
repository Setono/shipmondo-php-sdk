<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Request\Webhooks\Webhook as WebhookRequest;
use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;
use Webmozart\Assert\Assert;

/**
 * @covers \Setono\Shipmondo\Client\Client
 */
final class LiveClientTest extends TestCase
{
    private ?string $username = null;

    private ?string $apiKey = null;

    private ?ClientInterface $client = null;

    protected function setUp(): void
    {
        if ((bool) getenv('SHIPMONDO_LIVE') !== true) {
            self::markTestSkipped('The SHIPMONDO_LIVE environment variable is not set to true.');
        }

        $this->username = (string) getenv('SHIPMONDO_USERNAME');
        $this->apiKey = (string) getenv('SHIPMONDO_API_KEY');
    }

    /**
     * @test
     */
    public function it_creates_and_gets__and_deletes_webhooks(): void
    {
        $webhookName = uniqid('webhook-', true);

        $client = $this->getClient();
        $client->webhooks()->create(new WebhookRequest(
            $webhookName,
            'https://httpbin.org/post', // when a webhook is created, Shipmondo tests the endpoint immediately and expects an HTTP 200 response
            'ins4n3-3ncrypt10n-k3y',
            'create',
            WebhookRequest::RESOURCE_SHIPMENTS,
        ));

        $webhooks = $client->webhooks()->get();
        self::assertGreaterThanOrEqual(1, count($webhooks));

        $webhook = $webhooks->filter(fn (WebhookResponse $webhook) => $webhook->name === $webhookName)->first();
        self::assertNotFalse($webhook);

        $client->webhooks()->remove($webhook->id);
    }

    /**
     * @test
     */
    public function it_deletes_all_webhooks(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $client = $this->getClient();
            $client->webhooks()->create(new WebhookRequest(
                uniqid(sprintf('webhook-%d-', $i), true),
                'https://httpbin.org/post',
                'ins4n3-3ncrypt10n-k3y',
                'create',
                WebhookRequest::RESOURCE_SHIPMENTS,
            ));
        }

        $webhooks = $client->webhooks()->get();
        self::assertGreaterThanOrEqual(3, count($webhooks));

        $client->webhooks()->deleteAll();

        self::assertCount(0, $client->webhooks()->get());
    }

    private function getClient(): ClientInterface
    {
        if (null === $this->client) {
            Assert::notNull($this->username);
            Assert::notNull($this->apiKey);

            $this->client = new Client($this->username, $this->apiKey);
        }

        return $this->client;
    }
}
