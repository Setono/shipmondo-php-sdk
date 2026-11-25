<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Response;

use CuyZ\Valinor\Mapper\AsConverter;

/**
 * Maps a value the given backed enum doesn't know to null instead of failing, for a response field
 * whose values Shipmondo may extend beyond what the SDK models. Valinor detects the attribute by its
 * {@see AsConverter} marker, so it applies whichever mapper builder the client was given.
 *
 * ```
 * #[NullWhenUnknown(WebhookAction::class)]
 * public readonly ?WebhookAction $action,
 * ```
 *
 * A value that isn't a string isn't converted, so it still fails to map.
 */
#[AsConverter]
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class NullWhenUnknown
{
    /**
     * @param class-string<\BackedEnum> $enum a string-backed enum
     */
    public function __construct(private string $enum)
    {
    }

    /**
     * Valinor applies a converter only when its return type fits the field's type, so for Valinor
     * the return type is a template, inferred from the field, e.g. `?WebhookAction`. On a field that
     * isn't nullable, an unknown value still fails to map.
     *
     * @template T of \BackedEnum|null
     *
     * @param callable(string): T $next
     *
     * @valinor-return T
     */
    public function map(string $value, callable $next): ?\BackedEnum
    {
        return null === ($this->enum)::tryFrom($value) ? null : $next($value);
    }
}
