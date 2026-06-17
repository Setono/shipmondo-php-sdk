<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Request\PickupPointSearch;
use Webmozart\Assert\Assert;

/**
 * Hits the real Shipmondo API. Skipped unless SHIPMONDO_LIVE is set to a truthy value, with
 * SHIPMONDO_USERNAME and SHIPMONDO_API_KEY supplied in the environment.
 */
final class LiveClientTest extends TestCase
{
    private ?string $apiUser = null;

    private ?string $apiKey = null;

    private ?ClientInterface $client = null;

    protected function setUp(): void
    {
        if (!in_array(getenv('SHIPMONDO_LIVE'), ['1', 'true'], true)) {
            self::markTestSkipped('The SHIPMONDO_LIVE environment variable is not set to a truthy value.');
        }

        $this->apiUser = (string) getenv('SHIPMONDO_USERNAME');
        $this->apiKey = (string) getenv('SHIPMONDO_API_KEY');
    }

    #[Test]
    public function it_fetches_a_sales_order(): void
    {
        $salesOrder = $this->getClient()->salesOrders()->getById(125204848);

        self::assertSame(125204848, $salesOrder->id);
    }

    #[Test]
    public function it_lists_webhooks(): void
    {
        $webhooks = $this->getClient()->webhooks()->getPage();

        self::assertGreaterThanOrEqual(0, $webhooks->totalCount);
    }

    #[Test]
    public function it_searches_pickup_points(): void
    {
        $points = $this->getClient()->pickupPoints()->search(new PickupPointSearch('gls', 'DK', '9000'));

        self::assertNotEmpty($points);
    }

    private function getClient(): ClientInterface
    {
        if (null === $this->client) {
            Assert::notNull($this->apiUser);
            Assert::notNull($this->apiKey);

            $this->client = new Client($this->apiUser, $this->apiKey);
        }

        return $this->client;
    }
}
