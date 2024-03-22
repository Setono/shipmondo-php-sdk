<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\Webhooks;

final class Webhook
{
    public function __construct(
        public readonly int $id,
        public readonly string $endpoint,
        public readonly bool $active,
        public readonly string $name,
        public readonly string $action,
        public readonly string $resourceName,
    ) {
    }
}
