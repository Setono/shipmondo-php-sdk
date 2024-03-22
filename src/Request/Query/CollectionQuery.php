<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request\Query;

use Webmozart\Assert\Assert;

class CollectionQuery extends Query
{
    /**
     * @param array<string, scalar|\Stringable|\DateTimeInterface> $parameters
     */
    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);

        $this->page(1);
        $this->perPage(20);
    }

    public function page(int $page): self
    {
        Assert::greaterThanEq($page, 1);

        $this->parameters['page'] = $page;

        return $this;
    }

    public function incrementPage(int $increment = 1): self
    {
        Assert::greaterThanEq($increment, 1);
        Assert::keyExists($this->parameters, 'page');
        Assert::integer($this->parameters['page']);

        $this->parameters['page'] += $increment;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        Assert::greaterThanEq($perPage, 1);
        Assert::lessThanEq($perPage, 50);

        $this->parameters['per_page'] = $perPage;

        return $this;
    }
}
