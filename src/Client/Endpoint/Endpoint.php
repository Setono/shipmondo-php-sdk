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
use Setono\Shipmondo\Request\Query\CollectionQuery;
use Setono\Shipmondo\Response\Collection;
use Setono\Shipmondo\Response\Response;

/**
 * @template TResponse of Response
 *
 * @implements EndpointInterface<TResponse>
 */
abstract class Endpoint implements EndpointInterface, LoggerAwareInterface
{
    protected LoggerInterface $logger;

    public function __construct(
        protected readonly ClientInterface $client,
        protected readonly MapperBuilder $mapperBuilder,
        protected readonly string $endpoint,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @return Collection<TResponse>
     */
    public function get(CollectionQuery $query = null): Collection
    {
        /** @var class-string<Collection<TResponse>> $class */
        $class = 'Setono\Shipmondo\Response\Collection<' . static::getResponseClass() . '>';

        return $this
            ->mapperBuilder
            ->mapper()
            ->map(
                $class,
                $this->createSource(
                    $this->client->get($this->endpoint, $query ?? new CollectionQuery()),
                ),
            );
    }

    /**
     * @template TResource
     *
     * @param callable(CollectionQuery):Collection<TResource> $getter
     *
     * @return iterable<Collection<TResource>>
     */
    public static function paginate(callable $getter): iterable
    {
        $query = new CollectionQuery();

        while (true) {
            $collection = $getter($query);

            if ($collection->isEmpty()) {
                break;
            }

            $query->incrementPage();

            yield $collection;
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Takes a response and returns a Valinor Source representation
     */
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
     * @return class-string<TResponse>
     */
    abstract protected static function getResponseClass(): string;

    /**
     * Returns null if the response is not a collection
     *
     * @return array{page: int, pageSize: int, totalCount: int, totalPages: int, items: mixed}|null
     */
    private static function getCollection(ResponseInterface $response): ?array
    {
        $page = (int) $response->getHeaderLine('X-Current-Page');
        if (0 === $page) {
            return null;
        }

        return [
            'page' => $page,
            'pageSize' => (int) $response->getHeaderLine('X-Per-Page'),
            'totalCount' => (int) $response->getHeaderLine('X-Total-Count'),
            'totalPages' => (int) $response->getHeaderLine('X-Total-Pages'),
            'items' => json_decode((string) $response->getBody(), true, 512, \JSON_THROW_ON_ERROR),
        ];
    }
}
