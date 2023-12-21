<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Setono\Shipmondo\Client\Endpoint\PaymentGatewaysEndpointInterface;
use Setono\Shipmondo\Client\Endpoint\SalesOrdersEndpointInterface;
use Setono\Shipmondo\Client\Endpoint\ShipmentTemplatesEndpointInterface;
use Setono\Shipmondo\Client\Endpoint\WebhooksEndpointInterface;
use Setono\Shipmondo\Exception\InternalServerErrorException;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Exception\UnexpectedStatusCodeException;

interface ClientInterface
{
    /**
     * Returns the last request sent to the API if any requests has been sent
     */
    public function getLastRequest(): ?RequestInterface;

    /**
     * Returns the last response from the API, if any responses has been received
     */
    public function getLastResponse(): ?ResponseInterface;

    /**
     * @throws ClientExceptionInterface if an error happens while processing the request
     * @throws InternalServerErrorException if the server reports an internal server error
     * @throws NotFoundException if the request results in a 404
     * @throws UnexpectedStatusCodeException if the status code is not between 200 and 299, and it's not any of the above
     */
    public function request(RequestInterface $request): ResponseInterface;

    /**
     * @throws ClientExceptionInterface if an error happens while processing the request
     * @throws InternalServerErrorException if the server reports an internal server error
     * @throws NotFoundException if the request results in a 404
     * @throws UnexpectedStatusCodeException if the status code is not between 200 and 299, and it's not any of the above
     */
    public function get(string $uri, array $query = []): ResponseInterface;

    public function post(string $uri, array|object $body): ResponseInterface;

    public function paymentGateways(): PaymentGatewaysEndpointInterface;

    public function salesOrders(): SalesOrdersEndpointInterface;

    public function shipmentTemplates(): ShipmentTemplatesEndpointInterface;

    public function webhooks(): WebhooksEndpointInterface;
}
