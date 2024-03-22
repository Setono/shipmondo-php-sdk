<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Response;

use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

final class SingleResourceResponse
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
            throw new \InvalidArgumentException(sprintf(
                'The response does not contain an id. The status code was: %d and the response was: %s',
                $response->getStatusCode(),
                $response->getBody()->getContents(),
            ));
        }

        Assert::integer($data['id']);

        return new self($data['id'], $data);
    }
}
