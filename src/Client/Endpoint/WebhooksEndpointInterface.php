<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\Webhooks\Webhook as WebhookResponse;

/**
 * @extends EndpointInterface<WebhookResponse>
 * @extends CreatableEndpointInterface<WebhookResponse>
 * @extends DeletableEndpointInterface<WebhookResponse>
 */
interface WebhooksEndpointInterface extends EndpointInterface, CreatableEndpointInterface, DeletableEndpointInterface
{
    /**
     * @param Closure(WebhookResponse):bool|null $predicate
     */
    public function deleteAll(\Closure $predicate = null): void;
}
