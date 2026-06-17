<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Exception\InternalServerErrorException;
use Setono\Shipmondo\Exception\MalformedResponseException;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Exception\ResponseAwareException;
use Setono\Shipmondo\Exception\TooManyRequestsException;
use Setono\Shipmondo\Exception\UnauthorizedException;
use Setono\Shipmondo\Exception\UnexpectedStatusCodeException;
use Setono\Shipmondo\Exception\ValidationException;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

#[CoversClass(Client::class)]
final class ClientTest extends ShipmondoTestCase
{
    #[Test]
    public function it_sends_basic_auth_and_default_headers(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways', '[]', 200, ['X-Total-Pages' => '1']);
        $client = $this->client($http);

        $client->get('payment_gateways');

        $request = $http->sentRequests[0];
        self::assertSame('Basic ' . base64_encode('user:key'), $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertStringStartsWith('Setono-Shipmondo-PHP/', $request->getHeaderLine('User-Agent'));
    }

    #[Test]
    public function it_uses_the_production_host_by_default(): void
    {
        $http = (new ScriptedHttpClient())->on('https://app.shipmondo.com/api/public/v3/payment_gateways', '[]');
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $client = new Client('user', 'key', httpClient: $http, requestFactory: $psr17, streamFactory: $psr17);

        $client->get('payment_gateways');

        self::assertSame('app.shipmondo.com', $http->sentRequests[0]->getUri()->getHost());
    }

    #[Test]
    public function it_uses_the_sandbox_host_when_enabled(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways', '[]');

        $this->client($http)->get('payment_gateways');

        self::assertSame('sandbox.shipmondo.com', $http->sentRequests[0]->getUri()->getHost());
    }

    #[Test]
    public function it_builds_the_query_string(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders?page=2&per_page=25', '[]');

        $this->client($http)->get('sales_orders', ['page' => 2, 'per_page' => 25]);

        self::assertSame(self::BASE . '/sales_orders?page=2&per_page=25', (string) $http->sentRequests[0]->getUri());
    }

    /**
     * @param class-string<ResponseAwareException> $expectedException
     */
    #[Test]
    #[DataProvider('statusCodes')]
    public function it_maps_status_codes_to_typed_exceptions(int $status, string $expectedException, ?string $expectedError): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"error":"boom"}', $status);
        $client = $this->client($http);

        try {
            $client->get('sales_orders');
            self::fail('Expected an exception to be thrown');
        } catch (ResponseAwareException $e) {
            self::assertInstanceOf($expectedException, $e);
            self::assertSame($expectedError, $e->getError());
            self::assertSame($status, $e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return \Generator<string, array{int, class-string<ResponseAwareException>, ?string}>
     */
    public static function statusCodes(): \Generator
    {
        yield '401' => [401, UnauthorizedException::class, 'boom'];
        yield '404' => [404, NotFoundException::class, 'boom'];
        yield '422' => [422, ValidationException::class, 'boom'];
        yield '429' => [429, TooManyRequestsException::class, 'boom'];
        yield '500' => [500, InternalServerErrorException::class, 'boom'];
        yield '418' => [418, UnexpectedStatusCodeException::class, 'boom'];
    }

    #[Test]
    public function it_throws_on_a_non_json_body(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders/1', 'not json');

        $this->expectException(MalformedResponseException::class);

        $this->client($http)->get('sales_orders/1');
    }
}
