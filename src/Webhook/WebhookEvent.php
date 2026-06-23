<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Webhook;

use Setono\Shipmondo\Enum\WebhookAction;
use Setono\Shipmondo\Enum\WebhookResourceName;

/**
 * A verified incoming webhook delivered by Shipmondo, returned by {@see WebhookParser}.
 *
 * The five `SMD-*` request headers populate the metadata properties ({@see self::$action},
 * {@see self::$resourceType}, {@see self::$resourceId}, {@see self::$webhookId},
 * {@see self::$user}); the verified JWT payload populates {@see self::$webhookName},
 * {@see self::$url}, and {@see self::$data}.
 *
 * `action` and `resourceType` are typed enums; {@see WebhookParser} throws a
 * {@see \Setono\Shipmondo\Exception\MalformedWebhookException} if Shipmondo sends an action or
 * resource the SDK does not model, rather than passing an unknown value through.
 */
final class WebhookEvent
{
    /**
     * @param WebhookAction $action the `SMD-Action` header (e.g. `create`, `cancel`, `status_update`)
     * @param WebhookResourceName $resourceType the `SMD-Resource-Type` header (e.g. `Shipments`, `Orders`)
     * @param int|null $resourceId the `SMD-Resource-Id` header (the id of the affected resource)
     * @param int|null $webhookId the `SMD-Webhook-Id` header (the id of the webhook that fired)
     * @param string|null $user the `SMD-User` header (who triggered the action), or `null` if absent
     * @param string $webhookName the `webhook` claim — the name of the webhook that sent the message
     * @param string $url the `url` claim — the endpoint the message was delivered to
     * @param array<array-key, mixed> $data the `data` claim: the resource object, with the original
     *                                       snake_case keys — the same shape Shipmondo returns from the
     *                                       resource's GET endpoint
     */
    public function __construct(
        public readonly WebhookAction $action,
        public readonly WebhookResourceName $resourceType,
        public readonly ?int $resourceId,
        public readonly ?int $webhookId,
        public readonly ?string $user,
        public readonly string $webhookName,
        public readonly string $url,
        public readonly array $data,
    ) {
    }
}
