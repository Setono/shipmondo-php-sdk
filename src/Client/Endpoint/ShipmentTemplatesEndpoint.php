<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Response\Collection;
use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

/**
 * @extends Endpoint<ShipmentTemplate>
 */
final class ShipmentTemplatesEndpoint extends Endpoint implements ShipmentTemplatesEndpointInterface
{
    public function get(CollectionQuery $query = null): Collection
    {
        /** @var class-string<Collection<ShipmentTemplate>> $class */
        $class = 'Setono\Shipmondo\Response\Collection<Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate>';

        return $this->mapperBuilder->mapper()
            ->map($class, $this->createSource($this->client->get('shipment_templates', $query ?? new CollectionQuery())))
        ;
    }

    public function getById(int $id): ShipmentTemplate
    {
        return $this->mapperBuilder->mapper()
            ->map(
                ShipmentTemplate::class,
                $this->createSource($this->client->get('shipment_templates', ['id' => $id])),
            )
        ;
    }
}
