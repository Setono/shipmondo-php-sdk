<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\Normalizer\Configurator\NormalizerBuilderConfigurator;
use CuyZ\Valinor\NormalizerBuilder;
use Setono\Shipmondo\Request\Payload;

/**
 * The normalizer configuration that writes the SDK's request bodies ({@see Payload}) as the JSON Shipmondo expects:
 * snake_case keys, no `null` or `[]` values, and dates as DATE_ATOM. The {@see Client} applies it to every normalizer
 * builder, also one passed to its constructor, e.g. one with a cache.
 */
final class NormalizerConfigurator implements NormalizerBuilderConfigurator
{
    public function configureNormalizerBuilder(NormalizerBuilder $builder): NormalizerBuilder
    {
        return $builder
            ->registerTransformer(
                static fn (\DateTimeInterface $date): string => $date->format(\DATE_ATOM),
            )
            ->registerTransformer(
                /**
                 * @return array<string, mixed>
                 */
                static function (Payload $payload, callable $next): array {
                    /** @var array<string, mixed> $normalized */
                    $normalized = $next();

                    $result = [];
                    foreach ($normalized as $key => $value) {
                        // Strip both `null` AND `[]` so optional properties and empty collections
                        // are omitted from the JSON rather than serialized as `null` / `[]`.
                        if (null === $value || [] === $value) {
                            continue;
                        }

                        $result[self::camelToSnake($key)] = $value;
                    }

                    return $result;
                },
            )
        ;
    }

    private static function camelToSnake(string $key): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $key) ?? $key);
    }
}
