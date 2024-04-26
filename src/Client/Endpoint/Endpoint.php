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
use Setono\Shipmondo\Request\Query\Query;
use Setono\Shipmondo\Response\Collection;
use Setono\Shipmondo\Response\Response;
use Webmozart\Assert\Assert;

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
    public function get(Query $query = null): Collection
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
     * @return \Generator<array-key, Collection<TResource>>
     */
    public static function paginate(callable $getter): \Generator
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
            $data = json_decode(
                json: (string) $response->getBody(),
                associative: true,
                flags: \JSON_THROW_ON_ERROR,
            );
            Assert::isArray($data);

            if (array_is_list($data)) {
                return Source::array(self::prepareCollection($data, $response))->camelCaseKeys();
            }

            return Source::array($data)->camelCaseKeys();
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

    private static function prepareCollection(array $data, ResponseInterface $response): array
    {
        return array_filter([
            'page' => (int) $response->getHeaderLine('X-Current-Page'),
            'pageSize' => (int) $response->getHeaderLine('X-Per-Page'),
            'totalCount' => (int) $response->getHeaderLine('X-Total-Count'),
            'totalPages' => (int) $response->getHeaderLine('X-Total-Pages'),
            'items' => $data,
        ], static fn (mixed $value): bool => 0 !== $value);
    }
}
