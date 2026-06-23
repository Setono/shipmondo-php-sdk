<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Webhook;

/**
 * A verified incoming webhook delivered by Shipmondo, returned by {@see WebhookParser}.
 *
 * The five `SMD-*` request headers populate the metadata properties ({@see self::$action},
 * {@see self::$resourceType}, {@see self::$resourceId}, {@see self::$webhookId},
 * {@see self::$user}); the verified JWT payload populates {@see self::$webhookName},
 * {@see self::$url}, and {@see self::$data}.
 *
 * Following the SDK's lenient-read convention (cf. {@see \Setono\Shipmondo\Response\Webhook\Webhook}),
 * `action` and `resourceType` are plain strings so a new Shipmondo action/resource value never
 * causes parsing to fail.
 */
final class WebhookEvent
{
    /**
     * @param string $action the `SMD-Action` header, e.g. `create`, `cancel`, `status_update`
     * @param string $resourceType the `SMD-Resource-Type` header, e.g. `Shipments`, `Orders`
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
        public readonly string $action,
        public readonly string $resourceType,
        public readonly ?int $resourceId,
        public readonly ?int $webhookId,
        public readonly ?string $user,
        public readonly string $webhookName,
        public readonly string $url,
        public readonly array $data,
    ) {
    }
}
