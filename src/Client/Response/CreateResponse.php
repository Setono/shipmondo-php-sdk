<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Response;

use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

final class CreateResponse
{
    public function __construct(
        /**
         * The id of the created resource
         */
        public readonly int $id,
        /**
         * The data of the created resource
         */
        public readonly array $data,
    ) {
    }

    public static function fromHttpResponse(ResponseInterface $response): self
    {
        $data = json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
        Assert::isArray($data);

        if (!isset($data['id'])) {
            throw new \InvalidArgumentException('The response does not contain an id');
        }

        Assert::integer($data['id']);

        return new self($data['id'], $data);
    }
}
