<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Webhook\Webhook as WebhookRequest;
use Setono\Shipmondo\Response\Webhook\Webhook as WebhookResponse;

/**
 * @extends CollectionEndpoint<WebhookResponse>
 */
final class WebhooksEndpoint extends CollectionEndpoint
{
    /**
     * Create a webhook (`POST /webhooks`). Shipmondo immediately calls the endpoint to verify it,
     * expecting an HTTP 200 response.
     */
    public function create(WebhookRequest $request): WebhookResponse
    {
        return $this->createOne($request);
    }

    /**
     * Delete a webhook by id (`DELETE /webhooks/{id}`).
     */
    public function delete(int $id): void
    {
        $this->client->delete('webhooks', $id);
    }

    /**
     * Delete every webhook, optionally only those matching the given predicate. All matching
     * webhooks are collected across all pages first, then deleted, so deletes don't shift the
     * pagination window mid-walk.
     *
     * @param (\Closure(WebhookResponse):bool)|null $predicate
     */
    public function deleteAll(?\Closure $predicate = null): void
    {
        $webhooks = [];
        foreach ($this->paginate() as $webhook) {
            if (null === $predicate || $predicate($webhook)) {
                $webhooks[] = $webhook;
            }
        }

        foreach ($webhooks as $webhook) {
            $this->delete($webhook->id);
        }
    }

    protected static function getPath(): string
    {
        return 'webhooks';
    }

    /**
     * @return class-string<WebhookResponse>
     */
    protected static function getItemClass(): string
    {
        return WebhookResponse::class;
    }
}
