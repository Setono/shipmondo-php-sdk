<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

/**
 * @extends Endpoint<ShipmentTemplate>
 */
final class ShipmentTemplatesEndpoint extends Endpoint implements ShipmentTemplatesEndpointInterface
{
    public function getById(int $id): ShipmentTemplate
    {
        return $this->mapperBuilder->mapper()
            ->map(
                self::getResponseClass(),
                $this->createSource($this->client->get($this->endpoint, ['id' => $id])),
            )
        ;
    }

    protected static function getResponseClass(): string
    {
        return ShipmentTemplate::class;
    }
}
