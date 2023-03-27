<?php

declare(strict_types=1);

namespace Setono\Shipmondo\DTO;

/**
 * todo create interface for this class
 *
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
abstract class CollectionResponse implements \Countable, \IteratorAggregate
{
    public function __construct(
        /** @var list<T> $items */
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
     * @return T[]
     *
     * @psalm-return \ArrayIterator<int<0, max>, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
