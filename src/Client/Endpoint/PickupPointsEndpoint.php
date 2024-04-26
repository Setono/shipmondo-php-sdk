<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Response\PickupPoints\PickupPoint;

/**
 * @extends Endpoint<PickupPoint>
 */
final class PickupPointsEndpoint extends Endpoint implements PickupPointsEndpointInterface
{
    protected static function getResponseClass(): string
    {
        return PickupPoint::class;
    }
}
