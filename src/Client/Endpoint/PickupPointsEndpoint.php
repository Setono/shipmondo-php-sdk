<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\PickupPointSearch;
use Setono\Shipmondo\Response\PickupPoint\PickupPoint;

/**
 * Pickup points are special: `GET /pickup_points` returns a bare JSON array with NO pagination
 * headers, so this endpoint extends {@see Endpoint} (not {@see CollectionEndpoint}) and returns a
 * plain `list<PickupPoint>` rather than a `Collection`.
 */
final class PickupPointsEndpoint extends Endpoint
{
    /**
     * @return list<PickupPoint>
     */
    public function search(PickupPointSearch $query): array
    {
        $data = $this->client->get('pickup_points', $query->toArray());

        $points = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $points[] = $this->mapItem(PickupPoint::class, $row);
        }

        return $points;
    }
}
