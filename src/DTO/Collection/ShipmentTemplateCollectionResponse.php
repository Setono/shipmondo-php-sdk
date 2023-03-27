<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO\Collection;

use Setono\Shipmondo\DTO\Model\ShipmentTemplate;

/**
 * todo separate collection are necessary for Cuyz\Valinor to work: See https://github.com/CuyZ/Valinor/issues/364
 *
 * @extends CollectionResponse<ShipmentTemplate>
 */
final class ShipmentTemplateCollectionResponse extends CollectionResponse
{
}
