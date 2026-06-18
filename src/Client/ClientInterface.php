<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Setono\Shipmondo\Client\Endpoint\PaymentGatewaysEndpoint;
use Setono\Shipmondo\Client\Endpoint\PickupPointsEndpoint;
use Setono\Shipmondo\Client\Endpoint\SalesOrdersEndpoint;
use Setono\Shipmondo\Client\Endpoint\ShipmentTemplatesEndpoint;
use Setono\Shipmondo\Client\Endpoint\WebhooksEndpoint;
use Setono\Shipmondo\Exception\ShipmondoException;
use Setono\Shipmondo\Request\Payload;

interface ClientInterface
{
    /**
     * The last request sent to the API, or `null` if no request has been dispatched yet.
     */
    public function getLastRequest(): ?RequestInterface;

    /**
     * The last response received from the API, or `null` if no response has been received yet.
     */
    public function getLastResponse(): ?ResponseInterface;

    /**
     * @throws ClientExceptionInterface if an error happens while processing the request
     * @throws ShipmondoException if the response is non-2xx (concrete subtype depends on the status code)
     */
    public function request(RequestInterface $request): ResponseInterface;

    /**
     * GET the given URI and return the decoded JSON body. Pagination metadata for list endpoints
     * is carried in response headers, available via {@see self::getLastResponse()}.
     *
     * @param array<string, scalar|null> $query
     *
     * @return array<array-key, mixed>
     *
     * @throws ClientExceptionInterface if an error happens while processing the request
     * @throws ShipmondoException if the response is non-2xx, or the body is not valid JSON
     */
    public function get(string $uri, array $query = []): array;

    /**
     * POST the given typed request DTO to `$uri` and return the decoded JSON body. The body is
     * normalized to JSON via the SDK's `NormalizerBuilder` (null-skipping + snake_case keys).
     *
     * @return array<array-key, mixed>
     *
     * @throws ClientExceptionInterface if an error happens while processing the request
     * @throws ShipmondoException if the response is non-2xx, or the body is not valid JSON
     */
    public function post(string $uri, Payload $body): array;

    /**
     * DELETE `"{$uri}/{$id}"` and return the decoded JSON body (an empty array if the response has
     * no body).
     *
     * @return array<array-key, mixed>
     *
     * @throws ClientExceptionInterface if an error happens while processing the request
     * @throws ShipmondoException if the response is non-2xx
     */
    public function delete(string $uri, int $id): array;

    public function paymentGateways(): PaymentGatewaysEndpoint;

    public function pickupPoints(): PickupPointsEndpoint;

    public function salesOrders(): SalesOrdersEndpoint;

    public function shipmentTemplates(): ShipmentTemplatesEndpoint;

    public function webhooks(): WebhooksEndpoint;
}
