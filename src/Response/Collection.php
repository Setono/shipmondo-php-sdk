<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response;

/**
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Collection implements \Countable, \IteratorAggregate
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $totalPages,
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
     * @param Closure(T):bool $predicate
     *
     * @return self<T>
     */
    public function filter(\Closure $predicate): self
    {
        return new self(
            array_values(array_filter($this->items, $predicate)),
            $this->page,
            $this->pageSize,
            $this->totalPages,
        );
    }

    /**
     * @return T|false
     */
    public function first(): mixed
    {
        return $this->items[0] ?? false;
    }

    /**
     * @psalm-return \ArrayIterator<int<0, max>, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
