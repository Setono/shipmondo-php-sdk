<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\CreateResponse;
use Setono\Shipmondo\Request\Webhooks\Webhook;

final class WebhooksEndpoint extends Endpoint implements WebhooksEndpointInterface
{
    public function create(Webhook $webhook): CreateResponse
    {
        return CreateResponse::fromHttpResponse($this->client->post('webhooks', $webhook));
    }
}
