<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response\Collection;

/**
 * Passive data carrier for a single page of a paginated Shipmondo resource.
 *
 * Shipmondo paginates via response headers (`X-Current-Page`, `X-Per-Page`, `X-Total-Count`,
 * `X-Total-Pages`), so this carries those four values alongside the mapped items. The list body
 * is a bare JSON array of items, so the `Collection` itself is never Valinor-mapped — the endpoint
 * maps each item individually and constructs this wrapper from the headers. Pagination logic lives
 * in {@see \Setono\Shipmondo\Client\Endpoint\CollectionEndpoint::paginate()}, not here.
 *
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
final class Collection implements \IteratorAggregate, \Countable
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page = 1,
        public readonly int $pageSize = 0,
        public readonly int $totalCount = 0,
        public readonly int $totalPages = 1,
    ) {
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /**
     * @param \Closure(T):bool $predicate
     *
     * @return self<T>
     */
    public function filter(\Closure $predicate): self
    {
        return new self(
            array_values(array_filter($this->items, $predicate)),
            $this->page,
            $this->pageSize,
            $this->totalCount,
            $this->totalPages,
        );
    }

    /**
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
