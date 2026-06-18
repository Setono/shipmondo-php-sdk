<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\ShipmentTemplate\ShipmentTemplate;

/**
 * @extends CollectionEndpoint<ShipmentTemplate>
 */
final class ShipmentTemplatesEndpoint extends CollectionEndpoint
{
    /**
     * Look a shipment template up by id. Shipmondo exposes this as a `?id=` filter on the list
     * endpoint rather than a REST sub-path.
     */
    public function getById(int $id): ShipmentTemplate
    {
        return $this->getByQueryId($id);
    }

    protected static function getPath(): string
    {
        return 'shipment_templates';
    }

    /**
     * @return class-string<ShipmentTemplate>
     */
    protected static function getItemClass(): string
    {
        return ShipmentTemplate::class;
    }
}
