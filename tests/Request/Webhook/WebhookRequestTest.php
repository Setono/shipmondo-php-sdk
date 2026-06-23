<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Webhook;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookRequest::class)]
final class WebhookRequestTest extends TestCase
{
    #[Test]
    public function it_accepts_a_sufficiently_long_key(): void
    {
        $request = new WebhookRequest(key: 'a-sufficiently-long-webhook-signing-key');

        self::assertSame('a-sufficiently-long-webhook-signing-key', $request->key);
    }

    #[Test]
    public function it_allows_a_null_key(): void
    {
        $request = new WebhookRequest(name: 'no key yet');

        self::assertNull($request->key);
    }

    #[Test]
    public function it_rejects_a_key_that_is_too_short(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WebhookRequest(key: 'secret');
    }
}
