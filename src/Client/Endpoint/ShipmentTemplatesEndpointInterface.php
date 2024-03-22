<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\ShipmentTemplates\ShipmentTemplate;

/**
 * @extends EndpointInterface<ShipmentTemplate>
 */
interface ShipmentTemplatesEndpointInterface extends EndpointInterface
{
    public function getById(int $id): ShipmentTemplate;
}
