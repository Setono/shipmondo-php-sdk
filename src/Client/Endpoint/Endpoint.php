<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\Shipmondo\Client\ClientInterface;

abstract class Endpoint implements EndpointInterface, LoggerAwareInterface
{
    protected ClientInterface $client;

    protected MapperBuilder $mapperBuilder;

    protected LoggerInterface $logger;

    public function __construct(ClientInterface $client, MapperBuilder $mapperBuilder)
    {
        $this->client = $client;
        $this->mapperBuilder = $mapperBuilder;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function createSource(ResponseInterface $response): Source
    {
        try {
            $collection = self::getCollection($response);
            if (null !== $collection) {
                return Source::array($collection)->camelCaseKeys();
            }

            return Source::json((string) $response->getBody())->camelCaseKeys();
        } catch (\Throwable $e) {
            $lastRequest = $this->client->getLastRequest();

            $message = sprintf('There was an error turning the JSON into a Source representation. The error was: %s.', $e->getMessage());

            if (null !== $lastRequest) {
                $message .= sprintf(' The request was %s %s', $lastRequest->getMethod(), (string) $lastRequest->getUri());
            }

            $message .= sprintf("The inputted JSON was:\n%s", (string) $response->getBody());

            $this->logger->error($message);

            throw $e;
        }
    }

    /**
     * Returns null if the response is not a collection
     *
     * @return ?array{page: int, pageSize: int, totalCount: int, totalPages: int, items: mixed}
     */
    private static function getCollection(ResponseInterface $response): ?array
    {
        $ret = [
            'page' => (int) $response->getHeaderLine('X-Current-Page'),
            'pageSize' => (int) $response->getHeaderLine('X-Per-Page'),
            'totalCount' => (int) $response->getHeaderLine('X-Total-Count'),
            'totalPages' => (int) $response->getHeaderLine('X-Total-Pages'),
            'items' => json_decode((string) $response->getBody(), true, 512, \JSON_THROW_ON_ERROR),
        ];

        if (0 === $ret['page']) {
            return null;
        }

        return $ret;
    }
}
