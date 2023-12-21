<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Webhooks;

final class Webhook implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $endpoint,
        public string $key,
        public string $action,
        public string $resourceName,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'endpoint' => $this->endpoint,
            'key' => $this->key,
            'action' => $this->action,
            'resource_name' => $this->resourceName,
        ];
    }
}
