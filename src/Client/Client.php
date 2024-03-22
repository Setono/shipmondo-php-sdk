<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\MapperBuilder;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\Shipmondo\Client\Endpoint\PaymentGatewaysEndpoint;
use Setono\Shipmondo\Client\Endpoint\PaymentGatewaysEndpointInterface;
use Setono\Shipmondo\Client\Endpoint\SalesOrdersEndpoint;
use Setono\Shipmondo\Client\Endpoint\SalesOrdersEndpointInterface;
use Setono\Shipmondo\Client\Endpoint\ShipmentTemplatesEndpoint;
use Setono\Shipmondo\Client\Endpoint\ShipmentTemplatesEndpointInterface;
use Setono\Shipmondo\Client\Endpoint\WebhooksEndpoint;
use Setono\Shipmondo\Client\Endpoint\WebhooksEndpointInterface;
use Setono\Shipmondo\Exception\InternalServerErrorException;
use Setono\Shipmondo\Exception\NotAuthorizedException;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Exception\UnexpectedStatusCodeException;
use Setono\Shipmondo\Request\Query\Query;

final class Client implements ClientInterface, LoggerAwareInterface
{
    private ?RequestInterface $lastRequest = null;

    private ?ResponseInterface $lastResponse = null;

    private ?PaymentGatewaysEndpointInterface $paymentGatewaysEndpoint = null;

    private ?SalesOrdersEndpointInterface $salesOrdersEndpoint = null;

    private ?ShipmentTemplatesEndpointInterface $shipmentTemplatesEndpoint = null;

    private ?WebhooksEndpointInterface $webhooksEndpoint = null;

    private ?HttpClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private LoggerInterface $logger;

    private ?MapperBuilder $mapperBuilder = null;

    public function __construct(private readonly string $username, private readonly string $apiKey)
    {
        $this->logger = new NullLogger();
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    public function request(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader(
            'Authorization',
            sprintf('Basic %s', base64_encode($this->username . ':' . $this->apiKey)),
        )->withHeader('Accept', 'application/json');

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        $this->lastRequest = $request;
        $this->lastResponse = $this->getHttpClient()->sendRequest($this->lastRequest);

        self::assertStatusCode($this->lastResponse);

        return $this->lastResponse;
    }

    public function get(string $uri, Query|array $query = []): ResponseInterface
    {
        if (is_array($query)) {
            $query = new Query($query);
        }

        $url = sprintf(
            '%s/%s%s',
            $this->getBaseUri(),
            ltrim($uri, '/'),
            $query->isEmpty() ? '' : '?' . $query->toString(),
        );

        $request = $this->getRequestFactory()->createRequest('GET', $url);

        return $this->request($request);
    }

    public function post(string $uri, array|object $body): ResponseInterface
    {
        $url = sprintf('%s/%s', $this->getBaseUri(), ltrim($uri, '/'));

        $request = $this->getRequestFactory()
            ->createRequest('POST', $url)
            ->withBody(
                $this->getStreamFactory()
                    ->createStream(json_encode($body, \JSON_THROW_ON_ERROR)),
            )
        ;

        return $this->request($request);
    }

    public function delete(string $uri, int $id): ResponseInterface
    {
        $url = sprintf('%s/%s/%d', $this->getBaseUri(), ltrim($uri, '/'), $id);

        return $this->request($this->getRequestFactory()->createRequest('DELETE', $url));
    }

    public function paymentGateways(): PaymentGatewaysEndpointInterface
    {
        if (null === $this->paymentGatewaysEndpoint) {
            $this->paymentGatewaysEndpoint = new PaymentGatewaysEndpoint($this, $this->getMapperBuilder(), 'payment_gateways');
            $this->paymentGatewaysEndpoint->setLogger($this->logger);
        }

        return $this->paymentGatewaysEndpoint;
    }

    public function salesOrders(): SalesOrdersEndpointInterface
    {
        if (null === $this->salesOrdersEndpoint) {
            $this->salesOrdersEndpoint = new SalesOrdersEndpoint($this, $this->getMapperBuilder(), 'sales_orders');
            $this->salesOrdersEndpoint->setLogger($this->logger);
        }

        return $this->salesOrdersEndpoint;
    }

    public function shipmentTemplates(): ShipmentTemplatesEndpointInterface
    {
        if (null === $this->shipmentTemplatesEndpoint) {
            $this->shipmentTemplatesEndpoint = new ShipmentTemplatesEndpoint($this, $this->getMapperBuilder(), 'shipment_templates');
            $this->shipmentTemplatesEndpoint->setLogger($this->logger);
        }

        return $this->shipmentTemplatesEndpoint;
    }

    public function webhooks(): WebhooksEndpointInterface
    {
        if (null === $this->webhooksEndpoint) {
            $this->webhooksEndpoint = new WebhooksEndpoint($this, $this->getMapperBuilder(), 'webhooks');
            $this->webhooksEndpoint->setLogger($this->logger);
        }

        return $this->webhooksEndpoint;
    }

    public function setMapperBuilder(MapperBuilder $mapperBuilder): void
    {
        $this->mapperBuilder = $mapperBuilder;
    }

    public function getMapperBuilder(): MapperBuilder
    {
        if (null === $this->mapperBuilder) {
            $this->mapperBuilder = (new MapperBuilder())
                ->allowSuperfluousKeys()
            ;
        }

        return $this->mapperBuilder;
    }

    public function setHttpClient(?HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function setRequestFactory(?RequestFactoryInterface $requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function getBaseUri(): string
    {
        return 'https://app.shipmondo.com/api/public/v3';
    }

    private function getHttpClient(): HttpClientInterface
    {
        if (null === $this->httpClient) {
            $this->httpClient = Psr18ClientDiscovery::find();
        }

        return $this->httpClient;
    }

    private function getRequestFactory(): RequestFactoryInterface
    {
        if (null === $this->requestFactory) {
            $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        }

        return $this->requestFactory;
    }

    private function getStreamFactory(): StreamFactoryInterface
    {
        if (null === $this->streamFactory) {
            $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        }

        return $this->streamFactory;
    }

    private static function assertStatusCode(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        NotAuthorizedException::assert($response);
        NotFoundException::assert($response);
        InternalServerErrorException::assert($response);

        throw new UnexpectedStatusCodeException($response);
    }
}
