<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;

/**
 * @extends Endpoint<WebhookResponse>
 */
final class WebhooksEndpoint extends Endpoint implements WebhooksEndpointInterface
{
    /**
     * @use CreatableEndpointTrait<WebhookResponse>
     */
    use CreatableEndpointTrait;

    /**
     * @use RemovableEndpointTrait<WebhookResponse>
     */
    use RemovableEndpointTrait;

    protected static function getResponseClass(): string
    {
        return WebhookResponse::class;
    }

    public function deleteAll(): void
    {
        foreach (self::paginate($this->get(...)) as $collection) {
            foreach ($collection as $webhook) {
                $this->remove($webhook->id);
            }
        }
    }
}
