<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Request\Webhook\WebhookRequest;
use Setono\Shipmondo\Response\Webhook\Webhook;

/**
 * @extends CollectionEndpoint<Webhook>
 */
final class WebhooksEndpoint extends CollectionEndpoint
{
    /**
     * Create a webhook (`POST /webhooks`). Shipmondo immediately calls the endpoint to verify it,
     * expecting an HTTP 200 response.
     *
     * @throws \InvalidArgumentException if the request pairs a resource with an action it does not support
     */
    public function create(WebhookRequest $request): Webhook
    {
        // Fail fast on an invalid resource/action pair (a server 422) when both are set. When either
        // is null we stay lenient and let the API decide — required fields are enforced server-side.
        if (null !== $request->resourceName &&
            null !== $request->action &&
            !$request->resourceName->supports($request->action)
        ) {
            throw new \InvalidArgumentException(sprintf(
                'The "%s" action is not valid for the "%s" resource. Valid actions are: %s.',
                $request->action->value,
                $request->resourceName->value,
                implode(', ', array_map(
                    static fn (WebhookAction $action): string => $action->value,
                    $request->resourceName->actions(),
                )),
            ));
        }

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
     * @param (\Closure(Webhook):bool)|null $predicate
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
     * @return class-string<Webhook>
     */
    protected static function getItemClass(): string
    {
        return Webhook::class;
    }
}
