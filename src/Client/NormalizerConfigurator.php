<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client;

use CuyZ\Valinor\Normalizer\Configurator\NormalizeDateTimeFormat;
use CuyZ\Valinor\Normalizer\Configurator\NormalizeKeysToSnakeCase;
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
            ->configureWith(
                new NormalizeDateTimeFormat(\DATE_ATOM),
                new NormalizeKeysToSnakeCase(),
            )
            ->registerTransformer(
                /**
                 * Leaves out `null` and `[]`, so an optional property that isn't set, or an empty list, isn't sent
                 *
                 * @return array<array-key, mixed>
                 */
                static function (Payload $payload, callable $next): array {
                    /** @var array<array-key, mixed> $normalized */
                    $normalized = $next();

                    return array_filter($normalized, static fn (mixed $value): bool => null !== $value && [] !== $value);
                },
            )
        ;
    }
}
