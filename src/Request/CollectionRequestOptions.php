<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

use Webmozart\Assert\Assert;

/**
 * Immutable options for a paginated list request: which page and how many entries per page.
 *
 * `perPage` is capped at 50 (the largest page size any Shipmondo collection accepts); note that
 * sales orders cap at 25, so values in 26–50 are rejected by that endpoint with a 422.
 */
final class CollectionRequestOptions
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {
        Assert::greaterThanEq($page, 1);
        Assert::greaterThanEq($perPage, 1);
        Assert::lessThanEq($perPage, 50);
    }

    public static function new(): self
    {
        return new self();
    }

    public function withPage(int $page): self
    {
        return new self($page, $this->perPage);
    }

    public function withPerPage(int $perPage): self
    {
        return new self($this->page, $perPage);
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
