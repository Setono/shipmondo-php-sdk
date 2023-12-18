<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Client\Response\CreateResponse;
use Setono\Shipmondo\DTO\Model\SalesOrder;

interface SalesOrderEndpointInterface extends EndpointInterface
{
    public function create(SalesOrder $salesOrder): CreateResponse;
}
