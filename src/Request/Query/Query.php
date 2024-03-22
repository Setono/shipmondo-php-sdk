<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Query;

class Query implements \Stringable
{
    public function __construct(
        /**
         * @var array<string, scalar|\Stringable|\DateTimeInterface> $parameters
         */
        protected array $parameters = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->parameters;
    }

    public function toString(): string
    {
        return http_build_query(array_map(static function ($element) {
            return $element instanceof \DateTimeInterface ? $element->format(\DATE_ATOM) : $element;
        }, $this->parameters), '', '&', \PHP_QUERY_RFC3986);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
