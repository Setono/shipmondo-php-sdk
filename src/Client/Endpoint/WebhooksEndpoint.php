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
     * @use DeletableEndpointTrait<WebhookResponse>
     */
    use DeletableEndpointTrait;

    protected static function getResponseClass(): string
    {
        return WebhookResponse::class;
    }

    public function deleteAll(\Closure $predicate = null): void
    {
        foreach (self::paginate($this->get(...)) as $collection) {
            $collection = null === $predicate ? $collection : $collection->filter($predicate);

            foreach ($collection as $webhook) {
                $this->delete($webhook->id);
            }
        }
    }
}
