<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Exception\InternalServerErrorException;
use Setono\Shipmondo\Exception\InvalidUrlException;
use Setono\Shipmondo\Exception\MalformedResponseException;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Exception\ResponseAwareException;
use Setono\Shipmondo\Exception\TooManyRequestsException;
use Setono\Shipmondo\Exception\UnauthorizedException;
use Setono\Shipmondo\Exception\UnexpectedStatusCodeException;
use Setono\Shipmondo\Exception\ValidationException;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

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
        self::assertStringStartsWith('Setono-Shipmondo-PHP', $request->getHeaderLine('User-Agent'));
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

    #[Test]
    public function it_throws_when_the_body_is_a_json_scalar_rather_than_an_array(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders/1', '5');

        $this->expectException(MalformedResponseException::class);

        $this->client($http)->get('sales_orders/1');
    }

    #[Test]
    public function it_refuses_to_send_credentials_to_a_foreign_host(): void
    {
        $this->expectException(InvalidUrlException::class);

        $this->client(new ScriptedHttpClient())->get('https://evil.example.com/sales_orders');
    }

    #[Test]
    public function it_refuses_a_non_default_port_on_the_shipmondo_host(): void
    {
        $this->expectException(InvalidUrlException::class);

        $this->client(new ScriptedHttpClient())->get('https://sandbox.shipmondo.com:8443/api/public/v3/sales_orders');
    }

    #[Test]
    public function it_refuses_query_parameters_combined_with_an_absolute_url(): void
    {
        $this->expectException(InvalidUrlException::class);

        $this->client(new ScriptedHttpClient())->get(self::BASE . '/sales_orders', ['page' => 2]);
    }

    #[Test]
    public function it_allows_an_absolute_url_on_the_configured_host(): void
    {
        $url = self::BASE . '/payment_gateways';
        $http = (new ScriptedHttpClient())->on($url, '[]');

        $this->client($http)->get($url);

        self::assertSame($url, (string) $http->sentRequests[0]->getUri());
    }

    #[Test]
    public function the_last_request_and_response_are_null_before_any_call(): void
    {
        $client = $this->client(new ScriptedHttpClient());

        self::assertNull($client->getLastRequest());
        self::assertNull($client->getLastResponse());
    }

    #[Test]
    public function it_exposes_the_last_request_and_response_after_a_call(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways', '[]');
        $client = $this->client($http);

        $client->get('payment_gateways');

        $lastRequest = $client->getLastRequest();
        self::assertNotNull($lastRequest);
        self::assertSame('GET', $lastRequest->getMethod());

        $lastResponse = $client->getLastResponse();
        self::assertNotNull($lastResponse);
        self::assertSame(200, $lastResponse->getStatusCode());
    }
}
