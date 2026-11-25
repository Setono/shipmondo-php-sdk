<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\NormalizerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Setono\Shipmondo\Enum\PackingSlipFormat;
use Setono\Shipmondo\Exception\ConnectionException;
use Setono\Shipmondo\Exception\ForbiddenException;
use Setono\Shipmondo\Exception\InternalServerErrorException;
use Setono\Shipmondo\Exception\InvalidUrlException;
use Setono\Shipmondo\Exception\MalformedResponseException;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Exception\ResponseAwareException;
use Setono\Shipmondo\Exception\TooManyRequestsException;
use Setono\Shipmondo\Exception\UnauthorizedException;
use Setono\Shipmondo\Exception\UnexpectedClientErrorException;
use Setono\Shipmondo\Exception\UnexpectedStatusCodeException;
use Setono\Shipmondo\Exception\ValidationException;
use Setono\Shipmondo\Request\PickupPointSearch;
use Setono\Shipmondo\Request\SalesOrder\OrderLine;
use Setono\Shipmondo\Request\SalesOrder\Recipient;
use Setono\Shipmondo\Request\SalesOrder\SalesOrderRequest;
use Setono\Shipmondo\Request\Webhook\WebhookRequest;
use Setono\Shipmondo\Response\Collection\Collection;
use Setono\Shipmondo\Response\SalesOrder\SalesOrder;
use Setono\Shipmondo\Response\ShipmentTemplate\ShipmentTemplate;
use Setono\Shipmondo\Response\Webhook\Webhook;
use Setono\Shipmondo\ShipmondoTestCase;
use Setono\Shipmondo\TestDouble\NetworkFailure;
use Setono\Shipmondo\TestDouble\NonSeekableStream;
use Setono\Shipmondo\TestDouble\RecordingPsr17Factory;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;
use Setono\Shipmondo\TestDouble\UnreadableStream;

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
            self::assertSame($http->sentRequests[0], $e->getRequest());
        }
    }

    /**
     * @return \Generator<string, array{int, class-string<ResponseAwareException>, ?string}>
     */
    public static function statusCodes(): \Generator
    {
        yield '401' => [401, UnauthorizedException::class, 'boom'];
        yield '403' => [403, ForbiddenException::class, 'boom'];
        yield '404' => [404, NotFoundException::class, 'boom'];
        yield '422' => [422, ValidationException::class, 'boom'];
        yield '429' => [429, TooManyRequestsException::class, 'boom'];
        // every other 4xx is still a client error
        yield '400' => [400, UnexpectedClientErrorException::class, 'boom'];
        yield '409' => [409, UnexpectedClientErrorException::class, 'boom'];
        yield '418' => [418, UnexpectedClientErrorException::class, 'boom'];
        yield '499' => [499, UnexpectedClientErrorException::class, 'boom'];
        yield '500' => [500, InternalServerErrorException::class, 'boom'];
        yield '503' => [503, InternalServerErrorException::class, 'boom'];
        // 3xx is not a success: only 200-299 pass through.
        yield '300' => [300, UnexpectedStatusCodeException::class, 'boom'];
        yield '399' => [399, UnexpectedStatusCodeException::class, 'boom'];
    }

    #[Test]
    public function it_wraps_a_failure_of_the_http_client_so_every_failure_is_a_shipmondo_exception(): void
    {
        $http = (new ScriptedHttpClient())->fail(self::BASE . '/sales_orders?secret=s3cr3t', 'Connection refused');

        try {
            $this->client($http)->get('sales_orders', ['secret' => 's3cr3t']);
            self::fail('Expected an exception to be thrown');
        } catch (ConnectionException $e) {
            self::assertInstanceOf(NetworkFailure::class, $e->getPrevious());
            self::assertSame('Connection refused', $e->getPrevious()->getMessage());
            self::assertSame($http->sentRequests[0], $e->getRequest());
            // the query string is left out, like in the message of an exception that carries Shipmondo's response
            self::assertSame(
                'Could not get a response from Shipmondo [GET ' . self::BASE . '/sales_orders]. The HTTP client\'s exception is the previous exception',
                $e->getMessage(),
            );
        }
    }

    #[Test]
    public function it_has_no_response_when_the_http_client_fails(): void
    {
        $http = (new ScriptedHttpClient())->fail(self::BASE . '/sales_orders', 'Idle timeout reached');

        try {
            $this->client($http)->get('sales_orders');
            self::fail('Expected an exception to be thrown');
        } catch (ConnectionException $e) {
            self::assertNull($e->getResponse());
        }
    }

    #[Test]
    public function it_keeps_the_success_when_reading_its_body_fails_so_a_caller_knows_shipmondo_did_what_was_asked(): void
    {
        // e.g. a sales order that Shipmondo created, but whose answer took longer than the HTTP client's time limit
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders?secret=s3cr3t',
            new Response(201, ['Content-Type' => 'application/json'], new UnreadableStream('Unable to read stream contents: Idle timeout reached')),
        );

        try {
            $this->client($http)->post(self::BASE . '/sales_orders?secret=s3cr3t', new SalesOrderRequest(orderId: '27000'));
            self::fail('Expected an exception to be thrown');
        } catch (ConnectionException $e) {
            self::assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            self::assertSame('Unable to read stream contents: Idle timeout reached', $e->getPrevious()->getMessage());
            self::assertSame($http->sentRequests[0], $e->getRequest());

            $response = $e->getResponse();
            self::assertNotNull($response);
            self::assertSame(201, $response->getStatusCode());
            self::assertSame(['application/json'], $response->getHeader('Content-Type'));
            // the unreadable body is replaced, so reading it doesn't fail again
            self::assertSame('', $response->getBody()->getContents());

            // the query string is left out, like in the message of an exception that carries Shipmondo's response
            self::assertSame(
                'Shipmondo answered 201, but its response could not be read in full [POST ' . self::BASE . '/sales_orders]. The body\'s exception is the previous exception',
                $e->getMessage(),
            );
        }
    }

    /**
     * @param class-string<ResponseAwareException> $expectedException
     */
    #[Test]
    #[DataProvider('errorStatuses')]
    public function it_throws_the_exception_of_an_error_status_whose_body_cannot_be_read(int $status, string $expectedException): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', new Response($status, [], new UnreadableStream()));
        $client = $this->client($http);

        try {
            $client->get('sales_orders');
            self::fail('Expected an exception to be thrown');
        } catch (ResponseAwareException $e) {
            self::assertInstanceOf($expectedException, $e);
            self::assertNull($e->getError());
            self::assertSame('', $e->getResponse()->getBody()->getContents());
            self::assertSame($e->getResponse(), $client->getLastResponse());
        }
    }

    /**
     * @return iterable<string, array{int, class-string<ResponseAwareException>}>
     */
    public static function errorStatuses(): iterable
    {
        yield '503' => [503, InternalServerErrorException::class];
        yield '422' => [422, ValidationException::class];
        yield '302' => [302, UnexpectedStatusCodeException::class];
    }

    #[Test]
    public function it_reads_a_body_the_http_client_has_already_read(): void
    {
        // e.g. a PSR-18 client that logs the responses
        $body = Stream::create('{"id":5}');
        $body->getContents();

        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders/5', new Response(200, [], $body));

        self::assertSame(['id' => 5], $this->client($http)->get('sales_orders/5'));
    }

    #[Test]
    public function it_reads_a_non_seekable_body_once_and_keeps_it(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/sales_orders/5',
            new Response(200, [], new NonSeekableStream('{"id":5}')),
        );
        $client = $this->client($http);

        self::assertSame(['id' => 5], $client->get('sales_orders/5'));
        self::assertSame('{"id":5}', (string) $client->getLastResponse()?->getBody());
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

    /**
     * A caller can tell from the request whether Shipmondo did what was asked, e.g. created a sales order, although
     * its answer is unusable
     */
    #[Test]
    #[DataProvider('unreadableBodies')]
    public function a_success_it_cannot_read_carries_the_request_it_answered(string $body): void
    {
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', $body, 201);

        try {
            $this->client($http)->post('sales_orders', new SalesOrderRequest(orderId: '27000'));
            self::fail('Expected a MalformedResponseException');
        } catch (MalformedResponseException $e) {
            self::assertSame($http->sentRequests[0], $e->getRequest());
        }
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function unreadableBodies(): \Generator
    {
        yield 'not JSON' => ['not json'];
        yield 'a JSON scalar' => ['5'];
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
            [
                '{"order_id":"27000","archived":false,"enable_customs":false,"use_item_weight":true}',
                // the response's body, which the client reads in full
                '{"id":1}',
            ],
            $factory->createdStreams,
        );
    }

    /**
     * A consumer passes a mapper builder of its own, e.g. one with a cache, and the client applies the SDK's
     * configuration to it, so the consumer doesn't have to
     *
     * @param \Closure(Client): mixed $fetch
     */
    #[Test]
    #[DataProvider('realResponses')]
    public function it_maps_with_a_plain_mapper_builder_it_is_given_as_the_default_client_does(string $url, string $fixture, \Closure $fetch): void
    {
        $default = $this->client((new ScriptedHttpClient())->on($url, self::fixture($fixture)));
        $plain = $this->client((new ScriptedHttpClient())->on($url, self::fixture($fixture)), mapperBuilder: new MapperBuilder());

        self::assertEquals($fetch($default), $fetch($plain));
    }

    /**
     * @return \Generator<string, array{string, string, \Closure(Client): mixed}>
     */
    public static function realResponses(): \Generator
    {
        yield 'pickup points' => [
            self::BASE . '/pickup_points?carrier_code=gls&country_code=DK&zipcode=9000',
            'pickup_points.json',
            static fn (Client $client): array => $client->pickupPoints()->search(new PickupPointSearch('gls', 'DK', '9000')),
        ];
        yield 'a sales order' => [
            self::BASE . '/sales_orders/37707008',
            'sales_order.json',
            static fn (Client $client): SalesOrder => $client->salesOrders()->getById(37707008),
        ];
        yield 'a page of sales orders' => [
            self::BASE . '/sales_orders?page=1&per_page=20',
            'sales_orders_list.json',
            static fn (Client $client): Collection => $client->salesOrders()->getPage(),
        ];
        yield 'a shipment template' => [
            self::BASE . '/shipment_templates?id=31411785',
            'shipment_template_by_id.json',
            static fn (Client $client): ShipmentTemplate => $client->shipmentTemplates()->getById(31411785),
        ];
        yield 'a page of shipment templates' => [
            self::BASE . '/shipment_templates?page=1&per_page=20',
            'shipment_templates_list.json',
            static fn (Client $client): Collection => $client->shipmentTemplates()->getPage(),
        ];
        yield 'a created webhook' => [
            self::BASE . '/webhooks',
            'webhook.json',
            static fn (Client $client): Webhook => $client->webhooks()->create(new WebhookRequest(name: 'sdk-test')),
        ];
        yield 'a page of webhooks' => [
            self::BASE . '/webhooks?page=1&per_page=20',
            'webhooks_list.json',
            static fn (Client $client): Collection => $client->webhooks()->getPage(),
        ];
    }

    /**
     * Like a mapper builder, a normalizer builder of the consumer's own gets the SDK's configuration
     */
    #[Test]
    public function it_serializes_with_a_plain_normalizer_builder_it_is_given_as_the_default_client_does(): void
    {
        $request = new SalesOrderRequest(
            orderId: '27000',
            orderedAt: new \DateTimeImmutable('2018-10-17T13:25:44.123+02:00'),
            packingSlipFormat: PackingSlipFormat::A4Pdf,
            tags: ['b2b'],
            shipTo: new Recipient(name: 'Jane', zipCode: '9000', countryCode: 'DK', vatNo: 'DK123'),
            orderLines: [new OrderLine(itemName: 'Widget', quantity: 2)],
        );
        $default = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');
        $plain = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');

        $this->client($default)->post('sales_orders', $request);
        $this->client($plain, normalizerBuilder: new NormalizerBuilder())->post('sales_orders', $request);

        self::assertSame((string) $default->sentRequests[0]->getBody(), (string) $plain->sentRequests[0]->getBody());
    }

    /**
     * The client adds the SDK's configuration to a builder it is given, and keeps what the consumer configured on it
     */
    #[Test]
    public function it_keeps_the_configuration_of_a_mapper_builder_it_is_given(): void
    {
        $http = (new ScriptedHttpClient())->on(
            self::BASE . '/payment_gateways?id=1',
            '{"id":1,"name":" quickpay ","provider":"quick_pay","merchant_number":"67894321"}',
        );
        $mapperBuilder = (new MapperBuilder())->registerConverter(static fn (string $value): string => trim($value));

        self::assertSame('quickpay', $this->client($http, mapperBuilder: $mapperBuilder)->paymentGateways()->getById(1)->name);
    }

    #[Test]
    public function it_keeps_the_configuration_of_a_normalizer_builder_it_is_given(): void
    {
        // e.g. to send a field of Shipmondo's that the SDK doesn't model
        $normalizerBuilder = (new NormalizerBuilder())->registerTransformer(
            static function (OrderLine $line, callable $next): array {
                /** @var array<string, mixed> $normalized */
                $normalized = $next();

                return $normalized + ['custom_product_data' => ['colour' => 'red']];
            },
        );
        $http = (new ScriptedHttpClient())->on(self::BASE . '/sales_orders', '{"id":1}');

        $this->client($http, normalizerBuilder: $normalizerBuilder)->post(
            'sales_orders',
            new SalesOrderRequest(orderLines: [new OrderLine(itemName: 'Widget')]),
        );

        self::assertSame(
            '{"archived":false,"enable_customs":false,"use_item_weight":true,"order_lines":[{"item_name":"Widget","line_type":"item","quantity":1,"custom_product_data":{"colour":"red"}}]}',
            (string) $http->sentRequests[0]->getBody(),
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
}
