<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\DTO\Collection\ShipmentTemplateCollectionResponse;
use Setono\Shipmondo\DTO\Model\ShipmentTemplate;

final class ShipmentTemplatesEndpoint extends Endpoint implements ShipmentTemplatesEndpointInterface
{
    public function get(
        string $receiverCountry,
        string $senderCountry,
        int $page = 1,
        int $pageSize = 20,
    ): ShipmentTemplateCollectionResponse {
        return $this->mapperBuilder->mapper()
            ->map(
                ShipmentTemplateCollectionResponse::class,
                $this->createSource($this->client->get('shipment_templates')),
            )
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
