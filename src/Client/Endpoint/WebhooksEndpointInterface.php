<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Webhooks\Webhook as WebhookRequest;
use Setono\Shipmondo\Response\SingleResourceResponse;
use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;

/**
 * @extends EndpointInterface<WebhookResponse>
 */
interface WebhooksEndpointInterface extends EndpointInterface
{
    public function create(WebhookRequest $webhook): WebhookResponse;

    public function delete(int $id): SingleResourceResponse;

    public function deleteAll(): void;
}
