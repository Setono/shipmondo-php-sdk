<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\DTO\Collection\ShipmentTemplateCollectionResponse;
use Setono\Shipmondo\DTO\Model\ShipmentTemplate;

interface ShipmentTemplatesEndpointInterface extends EndpointInterface
{
    /**
     * @param int<1, max> $page
     * @param int<1, 50> $pageSize
     */
    public function get(
        string $receiverCountry,
        string $senderCountry,
        int $page = 1,
        int $pageSize = 20,
    ): ShipmentTemplateCollectionResponse;

    public function getById(int $id): ShipmentTemplate;
}
