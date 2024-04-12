<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

abstract class Request implements \JsonSerializable
{
    /**
     * @template TKey of array-key
     * @template TValue of mixed
     *
     * @param array<TKey, TValue> $data
     *
     * @return array<TKey, TValue>
     */
    public static function filter(array $data): array
    {
        return array_filter($data, static function (mixed $value): bool {
            if (null === $value) {
                return false;
            }

            if (is_string($value)) {
                return '' !== $value;
            }

            return true;
        });
    }
}
