<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

/**
 * Base class for every request DTO sent as a JSON body.
 *
 * Acts as a null-stripping, snake_case marker: the SDK's {@see \CuyZ\Valinor\NormalizerBuilder}
 * configuration (see {@see \Setono\Shipmondo\Client\Client::registerNormalizerTransformers()})
 * has a transformer that matches `Payload` and, for every `Payload` at every depth, converts the
 * object-normalized array's camelCase property names to the snake_case keys Shipmondo expects and
 * filters out `null` / `[]` entries — so optional DTO properties that default to `null` are absent
 * from the produced JSON rather than serialized as `"field": null`.
 *
 * Subclasses are `final class` with `public readonly` promoted properties. Constructor `Assert`
 * guards (where present) run at construction time only.
 */
abstract class Payload
{
}
