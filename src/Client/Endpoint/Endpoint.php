<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use Setono\Shipmondo\Client\ClientInterface;
use Setono\Shipmondo\Exception\MappingException;
use Setono\Shipmondo\Response\RawStamper;
use Setono\Shipmondo\Response\Resource;

abstract class Endpoint
{
    public function __construct(
        protected readonly ClientInterface $client,
        protected readonly MapperBuilder $mapperBuilder,
    ) {
    }

    /**
     * Map a single decoded JSON object into a typed {@see Resource}, then stamp `$raw` onto the
     * mapped object graph. Shipmondo responses are snake_case, so keys are recursively camelCased
     * to match the DTO property names (used for both the Valinor map and the `$raw` stamp).
     *
     * Converts Valinor's `MappingError` (a 2xx body that decoded as JSON but didn't fit the DTO)
     * into the SDK's typed {@see MappingException}, preserving the original as `$previous`.
     *
     * @template T of Resource
     *
     * @param class-string<T> $signature
     * @param array<array-key, mixed> $data
     *
     * @return T
     */
    protected function mapItem(string $signature, array $data): Resource
    {
        $camelCased = self::camelCaseKeys($data);

        try {
            $item = $this->mapperBuilder->mapper()->map($signature, $camelCased);

            RawStamper::stamp($item, $camelCased);

            return $item;
        } catch (MappingError $e) {
            $response = $this->client->getLastResponse();
            $request = $this->client->getLastRequest();

            if (null === $response) {
                throw $e;
            }

            $context = null === $request
                ? ''
                : sprintf(' [%s %s]', $request->getMethod(), (string) $request->getUri()->withQuery('')->withFragment(''));

            throw new MappingException(
                $response,
                sprintf('Could not map response body to %s%s: %s', $signature, $context, $e->getMessage()),
                $e,
                request: $request,
            );
        }
    }

    /**
     * Recursively convert snake_case array keys to camelCase so they match DTO property names.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    protected static function camelCaseKeys(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::camelCaseKeys($value);
            }

            $result[is_string($key) ? self::snakeToCamel($key) : $key] = $value;
        }

        return $result;
    }

    private static function snakeToCamel(string $key): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
    }
}
