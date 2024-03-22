<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Request\Webhooks\Webhook as WebhookRequest;
use Setono\Shipmondo\Response\Collection;
use Setono\Shipmondo\Response\SingleResourceResponse;
use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;

/**
 * @extends Endpoint<WebhookResponse>
 */
final class WebhooksEndpoint extends Endpoint implements WebhooksEndpointInterface
{
    /**
     * @return Collection<WebhookResponse>
     */
    public function get(CollectionQuery $query = null): Collection
    {
        /** @var class-string<Collection<WebhookResponse>> $class */
        $class = 'Setono\Shipmondo\Response\Collection<Setono\Shipmondo\Response\Webhooks\Webhook>';

        return $this->mapperBuilder->mapper()
            ->map($class, $this->createSource($this->client->get('webhooks', $query ?? new CollectionQuery())))
        ;
    }

    public function create(WebhookRequest $webhook): SingleResourceResponse
    {
        return SingleResourceResponse::fromHttpResponse($this->client->post('webhooks', $webhook));
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
