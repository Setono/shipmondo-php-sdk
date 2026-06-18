<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Psr\Http\Message\ResponseInterface;
use Setono\Shipmondo\Exception\NotFoundException;
use Setono\Shipmondo\Request\CollectionRequestOptions;
use Setono\Shipmondo\Response\Collection\Collection;
use Setono\Shipmondo\Response\Resource;
use Webmozart\Assert\Assert;

/**
 * Abstract base for leaf endpoints that return a header-paginated `Collection<T>`.
 *
 * Shipmondo list endpoints return a bare JSON array of items and carry pagination in the response
 * headers (`X-Current-Page`, `X-Per-Page`, `X-Total-Count`, `X-Total-Pages`), so the `Collection`
 * itself is never Valinor-mapped — {@see self::mapPage()} maps each item and reads the headers off
 * the client's last response.
 *
 * @template T of Resource
 * @extends ResourceEndpoint<T>
 */
abstract class CollectionEndpoint extends ResourceEndpoint
{
    /**
     * Fetch a single page of the collection.
     *
     * @return Collection<T>
     */
    public function getPage(?CollectionRequestOptions $opts = null): Collection
    {
        $opts ??= new CollectionRequestOptions();

        return $this->mapPage($this->client->get(static::getPath(), $opts->toArray()));
    }

    /**
     * Walk all pages, yielding every item across all pages in server order. Stops when a page comes
     * back empty or the known total-page count is reached.
     *
     * @return \Generator<int, T>
     */
    public function paginate(?CollectionRequestOptions $opts = null): \Generator
    {
        $opts ??= new CollectionRequestOptions();
        $page = $opts->page;

        while (true) {
            $collection = $this->getPage($opts->withPage($page));

            if ($collection->isEmpty()) {
                return;
            }

            yield from $collection->items;

            if (0 !== $collection->totalPages && $page >= $collection->totalPages) {
                return;
            }

            ++$page;
        }
    }

    /**
     * Fetch a single item via the `?id=` query parameter (used by endpoints that look an item up by
     * id through a filtered list rather than a REST sub-path). The response may be either a single
     * object or a one-element list.
     *
     * @return T
     *
     * @throws NotFoundException if no item with the given id is returned
     */
    protected function getByQueryId(int $id): Resource
    {
        $data = $this->client->get(static::getPath(), ['id' => $id]);

        $item = array_is_list($data) ? ($data[0] ?? null) : $data;

        if (!is_array($item)) {
            $response = $this->client->getLastResponse();
            Assert::notNull($response);

            throw new NotFoundException(
                $response,
                sprintf('No resource found at "%s" with id %d.', static::getPath(), $id),
            );
        }

        return $this->mapItem(static::getItemClass(), $item);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return Collection<T>
     */
    private function mapPage(array $data): Collection
    {
        $response = $this->client->getLastResponse();

        /** @var list<T> $items */
        $items = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $items[] = $this->mapItem(static::getItemClass(), $row);
        }

        return new Collection(
            $items,
            self::header($response, 'X-Current-Page', 1),
            self::header($response, 'X-Per-Page', count($items)),
            self::header($response, 'X-Total-Count', count($items)),
            self::header($response, 'X-Total-Pages', 1),
        );
    }

    private static function header(?ResponseInterface $response, string $name, int $default): int
    {
        if (null === $response) {
            return $default;
        }

        $value = $response->getHeaderLine($name);

        return '' === $value ? $default : (int) $value;
    }
}
