<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\MapperBuilder;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Setono\Shipmondo\Client\Endpoint\EndpointInterface;

/**
 * @covers \Setono\Shipmondo\Client\Client
 */
final class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_sends_expected_request(): void
    {
        $username = 'username';
        $apiKey = 'api_key';
        $expectedAuthorizationHeader = 'Basic ' . base64_encode("$username:$apiKey");

        $httpClient = new MockHttpClient();

        $client = new Client($username, $apiKey);
        $client->setHttpClient($httpClient);
        $client->get('/endpoint/sub', [
            'empty' => null,
            'param1' => 'value 1',
            'param2' => 'value 2',
            'date' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-02-15 11:50:00'),
        ]);

        self::assertNotNull($httpClient->lastRequest);
        self::assertNotNull($client->getLastResponse());
        self::assertNotNull($client->getLastRequest());
        self::assertSame('GET', $httpClient->lastRequest->getMethod());
        self::assertSame(
            'https://app.shipmondo.com/api/public/v3/endpoint/sub?param1=value%201&param2=value%202&date=2023-02-15T11%3A50%3A00%2B00%3A00',
            (string) $httpClient->lastRequest->getUri(),
        );
        self::assertSame($expectedAuthorizationHeader, $httpClient->lastRequest->getHeaderLine('Authorization'));
    }

    /**
     * @test
     */
    public function it_returns_same_endpoints(): void
    {
        $client = new Client('apiKey', 'apiSecret');
        $endpoints = ['paymentGateways', 'shipmentTemplates'];
        foreach ($endpoints as $endpoint) {
            /** @var EndpointInterface $endpointObject */
            $endpointObject = $client->{$endpoint}();

            // this checks that we get the same instance for each call
            self::assertSame($endpointObject, $client->{$endpoint}());
        }
    }

    /**
     * @test
     */
    public function it_returns_same_mapper_builder(): void
    {
        $client = new Client('apiKey', 'apiSecret');

        $mapperBuilder = $client->getMapperBuilder();

        self::assertSame($mapperBuilder, $client->getMapperBuilder());
    }

    /**
     * @test
     */
    public function it_allows_to_set_the_mapper_builder(): void
    {
        $client = new Client('apiKey', 'apiSecret');

        $mapperBuilder = $client->getMapperBuilder();
        $client->setMapperBuilder(new MapperBuilder());

        self::assertNotSame($mapperBuilder, $client->getMapperBuilder());
    }
}

final class MockHttpClient implements HttpClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return new Response();
    }
}
