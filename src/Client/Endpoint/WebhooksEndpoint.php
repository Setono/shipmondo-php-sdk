<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Webhooks\Webhook as WebhookRequest;
use Setono\Shipmondo\Response\SingleResourceResponse;
use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;

/**
 * @extends Endpoint<WebhookResponse>
 */
final class WebhooksEndpoint extends Endpoint implements WebhooksEndpointInterface
{
    protected static function getResponseClass(): string
    {
        return WebhookResponse::class;
    }

    public function create(WebhookRequest $webhook): WebhookResponse
    {
        return $this->_create(self::getResponseClass(), $webhook);
    }

    public function delete(int $id): SingleResourceResponse
    {
        return SingleResourceResponse::fromHttpResponse($this->client->delete('webhooks', $id));
    }

    public function deleteAll(): void
    {
        foreach (self::paginate($this->get(...)) as $collection) {
            foreach ($collection as $webhook) {
                $this->delete($webhook->id);
            }
        }
    }
}
