<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

final class PaymentGatewaysEndpointTest extends ShipmondoTestCase
{
    #[Test]
    public function it_gets_a_payment_gateway_by_id_via_query_param(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/payment_gateways?id=1',
            '{"id":1,"name":"quickpay","provider":"quick_pay","merchant_number":"67894321"}',
        );

        $gateway = $this->client($http)->paymentGateways()->getById(1);

        self::assertSame(1, $gateway->id);
        self::assertSame('quickpay', $gateway->name);
        self::assertSame('67894321', $gateway->merchantNumber);
        self::assertSame(self::BASE . '/payment_gateways?id=1', (string) $http->sentRequests[0]->getUri());
    }

    #[Test]
    public function it_unwraps_a_single_element_list_response(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/payment_gateways?id=1',
            '[{"id":1,"name":"quickpay","provider":"quick_pay","merchant_number":"67894321"}]',
        );

        $gateway = $this->client($http)->paymentGateways()->getById(1);

        self::assertSame(1, $gateway->id);
    }

    #[Test]
    public function it_throws_not_found_when_the_list_is_empty(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways?id=999', '[]');

        $this->expectException(NotFoundException::class);

        $this->client($http)->paymentGateways()->getById(999);
    }
}
