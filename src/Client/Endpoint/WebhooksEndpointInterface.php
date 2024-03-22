<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;

/**
 * @extends EndpointInterface<WebhookResponse>
 * @extends CreatableEndpointInterface<WebhookResponse>
 * @extends RemovableEndpointInterface<WebhookResponse>
 */
interface WebhooksEndpointInterface extends EndpointInterface, CreatableEndpointInterface, RemovableEndpointInterface
{
    public function deleteAll(): void;
}
