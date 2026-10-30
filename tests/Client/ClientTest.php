<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\NormalizerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Exception\InternalServerErrorException;
use Setono\Shipmondo\Exception\InvalidUrlException;
use Setono\Shipmondo\Exception\MalformedResponseException;
use Setono\Shipmondo\Exception\MappingException;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Exception\ResponseAwareException;
use Setono\Shipmondo\Exception\TooManyRequestsException;
use Setono\Shipmondo\Exception\UnauthorizedException;
use Setono\Shipmondo\Exception\UnexpectedStatusCodeException;
use Setono\Shipmondo\Exception\ValidationException;
use Setono\Shipmondo\Request\SalesOrder\SalesOrderRequest;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\NonSeekableStream;
use Setono\Shipmondo\TestDouble\RecordingPsr17Factory;
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
        $psr17 = new Psr17Factory();
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
        // 3xx is not a success: only 200-299 pass through.
        yield '300' => [300, UnexpectedStatusCodeException::class, 'boom'];
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

    #[Test]
    #[DataProvider('contentTypes')]
    public function it_sets_a_json_content_type_only_on_methods_that_carry_a_body(string $method, string $expectedContentType): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{}');

        $this->client($http)->request((new Psr17Factory())->createRequest($method, self::BASE . '/sales_orders'));

        self::assertSame($expectedContentType, $http->sentRequests[0]->getHeaderLine('Content-Type'));
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function contentTypes(): \Generator
    {
        yield 'GET' => ['GET', ''];
        yield 'DELETE' => ['DELETE', ''];
        yield 'POST' => ['POST', 'application/json'];
        yield 'PUT' => ['PUT', 'application/json'];
        yield 'PATCH' => ['PATCH', 'application/json'];
    }

    #[Test]
    public function it_keeps_an_explicit_content_type(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{}');

        $this->client($http)->request(
            (new Psr17Factory())->createRequest('PUT', self::BASE . '/sales_orders')->withHeader('Content-Type', 'text/csv'),
        );

        self::assertSame('text/csv', $http->sentRequests[0]->getHeaderLine('Content-Type'));
    }

    /**
     * @param \Closure(Client): object $accessor
     */
    #[Test]
    #[DataProvider('endpointAccessors')]
    public function it_memoizes_each_endpoint(\Closure $accessor): void
    {
        $client = $this->client(new ScriptedHttpClient());

        self::assertSame($accessor($client), $accessor($client));
    }

    /**
     * @return \Generator<string, array{\Closure(Client): object}>
     */
    public static function endpointAccessors(): \Generator
    {
        yield 'payment gateways' => [static fn (Client $client): object => $client->paymentGateways()];
        yield 'pickup points' => [static fn (Client $client): object => $client->pickupPoints()];
        yield 'sales orders' => [static fn (Client $client): object => $client->salesOrders()];
        yield 'shipment templates' => [static fn (Client $client): object => $client->shipmentTemplates()];
        yield 'webhooks' => [static fn (Client $client): object => $client->webhooks()];
    }

    #[Test]
    public function it_uses_the_injected_request_and_stream_factories(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');
        $factory = new RecordingPsr17Factory();
        $client = new Client('user', 'key', sandbox: true, httpClient: $http, requestFactory: $factory, streamFactory: $factory);

        $client->post('sales_orders', new SalesOrderRequest(orderId: '27000'));

        self::assertSame(['POST ' . self::BASE . '/sales_orders'], $factory->createdRequests);
        self::assertSame(
            ['{"order_id":"27000","archived":false,"enable_customs":false,"use_item_weight":true}'],
            $factory->createdStreams,
        );
    }

    #[Test]
    public function it_maps_with_the_injected_mapper_builder_as_given(): void
    {
        // A bare MapperBuilder rejects superfluous keys, so the unmodelled "foo" field fails to map.
        // That proves the injected builder is used as-is instead of the SDK default.
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways?id=1', self::paymentGatewayWithAnUnknownField());
        $psr17 = new Psr17Factory();
        $client = new Client('user', 'key', sandbox: true, httpClient: $http, requestFactory: $psr17, streamFactory: $psr17, mapperBuilder: new MapperBuilder());

        $this->expectException(MappingException::class);

        $client->paymentGateways()->getById(1);
    }

    #[Test]
    public function configure_mapper_builder_applies_the_sdk_mapper_configuration_to_a_custom_builder(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways?id=1', self::paymentGatewayWithAnUnknownField());
        $psr17 = new Psr17Factory();
        $client = new Client(
            'user',
            'key',
            sandbox: true,
            httpClient: $http,
            requestFactory: $psr17,
            streamFactory: $psr17,
            mapperBuilder: Client::configureMapperBuilder(new MapperBuilder()),
        );

        $gateway = $client->paymentGateways()->getById(1);

        self::assertSame(1, $gateway->id);
        self::assertSame('bar', $gateway->raw['foo']);
    }

    #[Test]
    public function it_normalizes_with_the_injected_normalizer_builder_as_given(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');
        $psr17 = new Psr17Factory();
        $client = new Client('user', 'key', sandbox: true, httpClient: $http, requestFactory: $psr17, streamFactory: $psr17, normalizerBuilder: new NormalizerBuilder());

        $client->post('sales_orders', new SalesOrderRequest(orderId: '27000'));

        // Without the SDK's Payload transformer, keys stay camelCase and nulls are kept.
        $body = self::sentBody($http);
        self::assertSame('27000', $body['orderId']);
        self::assertArrayHasKey('sourceName', $body);
        self::assertNull($body['sourceName']);
    }

    #[Test]
    public function register_normalizer_transformers_applies_the_sdk_transformers_to_a_custom_builder(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');
        $psr17 = new Psr17Factory();
        $client = new Client(
            'user',
            'key',
            sandbox: true,
            httpClient: $http,
            requestFactory: $psr17,
            streamFactory: $psr17,
            normalizerBuilder: Client::registerNormalizerTransformers(new NormalizerBuilder()),
        );

        $client->post('sales_orders', new SalesOrderRequest(
            orderId: '27000',
            orderedAt: new \DateTimeImmutable('2018-10-17T13:25:44+02:00'),
        ));

        self::assertSame(
            ['order_id' => '27000', 'ordered_at' => '2018-10-17T13:25:44+02:00', 'archived' => false, 'enable_customs' => false, 'use_item_weight' => true],
            self::sentBody($http),
        );
    }

    #[Test]
    public function it_strips_a_leading_slash_from_a_relative_path(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/payment_gateways', '[]');

        $this->client($http)->get('/payment_gateways');

        self::assertSame(self::BASE . '/payment_gateways', (string) $http->sentRequests[0]->getUri());
    }

    #[Test]
    public function it_treats_a_path_that_merely_contains_a_url_as_relative(): void
    {
        // Only a URI that STARTS with a scheme is absolute; a URL further along is just part of the path.
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders?return_url=https://example.com', '[]');

        $this->client($http)->get('sales_orders?return_url=https://example.com');

        self::assertSame(self::BASE . '/sales_orders?return_url=https://example.com', (string) $http->sentRequests[0]->getUri());
    }

    #[Test]
    #[DataProvider('foreignHosts')]
    public function it_names_the_refused_host_and_sends_nothing(string $url, string $reportedHost): void
    {
        $http = new ScriptedHttpClient();

        try {
            $this->client($http)->get($url);
            self::fail('Expected an InvalidUrlException');
        } catch (InvalidUrlException $e) {
            self::assertSame(
                sprintf(
                    'Refusing to send a request to host "%s" — the Shipmondo base host is "sandbox.shipmondo.com". '
                    . 'The SDK only sends auth credentials to its configured host.',
                    $reportedHost,
                ),
                $e->getMessage(),
            );
        }

        self::assertSame([], $http->sentRequests);
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function foreignHosts(): \Generator
    {
        yield 'foreign host' => ['https://evil.example.com/sales_orders', 'evil.example.com'];
        yield 'upper-case scheme and host' => ['HTTPS://EVIL.example.com/sales_orders', 'evil.example.com'];
        yield 'unparseable host' => ['https:///sales_orders', '(unparseable)'];
    }

    #[Test]
    #[DataProvider('equivalentAbsoluteUrls')]
    public function it_accepts_an_absolute_url_on_the_configured_host_however_it_is_spelled(string $url, string $sentUrl): void
    {
        $http = (new ScriptedHttpClient())->on($sentUrl, '[]');

        $this->client($http)->get($url);

        self::assertSame($sentUrl, (string) $http->sentRequests[0]->getUri());
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function equivalentAbsoluteUrls(): \Generator
    {
        yield 'upper-case host' => ['https://SANDBOX.Shipmondo.com/api/public/v3/payment_gateways', self::BASE . '/payment_gateways'];
        yield 'explicit https default port' => ['https://sandbox.shipmondo.com:443/api/public/v3/payment_gateways', self::BASE . '/payment_gateways'];
        yield 'upper-case scheme with its default port' => ['HTTPS://sandbox.shipmondo.com:443/api/public/v3/payment_gateways', self::BASE . '/payment_gateways'];
        yield 'explicit http default port' => ['http://sandbox.shipmondo.com:80/api/public/v3/payment_gateways', 'http://sandbox.shipmondo.com/api/public/v3/payment_gateways'];
    }

    #[Test]
    public function it_throws_on_an_empty_get_body(): void
    {
        // Only delete() tolerates an empty body.
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders/1', '');

        $this->expectException(MalformedResponseException::class);

        $this->client($http)->get('sales_orders/1');
    }

    #[Test]
    public function it_keeps_the_query_string_out_of_the_decode_error_message(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders?api_key=secret', 'not json');

        try {
            $this->client($http)->get('sales_orders', ['api_key' => 'secret']);
            self::fail('Expected a MalformedResponseException');
        } catch (MalformedResponseException $e) {
            self::assertSame('Could not decode response body as JSON [GET ' . self::BASE . '/sales_orders]: Syntax error.', $e->getMessage());
        }
    }

    #[Test]
    public function delete_returns_the_decoded_body(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/webhooks/5', '{"id":5}');

        self::assertSame(['id' => 5], $this->client($http)->delete('webhooks', 5));
    }

    #[Test]
    public function delete_tolerates_a_whitespace_only_body_and_a_leading_slash(): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/webhooks/5', " \n");

        self::assertSame([], $this->client($http)->delete('/webhooks', 5));
        self::assertSame(self::BASE . '/webhooks/5', (string) $http->sentRequests[0]->getUri());
    }

    #[Test]
    public function it_pre_reads_an_error_body_so_a_non_seekable_stream_still_yields_the_error(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders',
            new Response(422, [], new NonSeekableStream('{"error":"boom"}')),
        );

        try {
            $this->client($http)->get('sales_orders');
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame('boom', $e->getError());
            self::assertSame('The status code was: 422. [GET ' . self::BASE . '/sales_orders] The body was: {"error":"boom"}.', $e->getMessage());
        }
    }

    private static function paymentGatewayWithAnUnknownField(): string
    {
        return '{"id":1,"name":"quickpay","provider":"quick_pay","merchant_number":"67894321","foo":"bar"}';
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function sentBody(ScriptedHttpClient $http): array
    {
        $body = json_decode((string) $http->sentRequests[0]->getBody(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        return $body;
    }
}
