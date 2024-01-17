<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\CreateResponse;
use Setono\Shipmondo\Request\Webhooks\Webhook;

interface WebhooksEndpointInterface extends EndpointInterface
{
    /**
     * @return list<CreateResponse>
     */
    public function create(Webhook $webhook): array;
}
