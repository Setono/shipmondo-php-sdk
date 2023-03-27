<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO\Collection;

use Setono\Shipmondo\DTO\Model\PaymentGateway;

/**
 * todo separate collection are necessary for Cuyz\Valinor to work: See https://github.com/CuyZ/Valinor/issues/364
 *
 * @extends CollectionResponse<PaymentGateway>
 */
final class PaymentGatewayCollectionResponse extends CollectionResponse
{
}
