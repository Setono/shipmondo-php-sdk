<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Request;

/**
 * Base class for every request DTO sent as a JSON body.
 *
 * Acts as a null-stripping marker: the SDK's {@see \CuyZ\Valinor\NormalizerBuilder} configuration
 * ({@see \Setono\Shipmondo\Client\NormalizerConfigurator}) has a transformer that matches `Payload`
 * and, for every `Payload` at every depth, filters out `null` / `[]` entries — so optional DTO
 * properties that default to `null` are absent from the produced JSON rather than serialized as
 * `"field": null`. Valinor's `NormalizeKeysToSnakeCase` turns the camelCase property names into the
 * snake_case keys Shipmondo expects.
 *
 * Subclasses are `final class` with **mutable** `public` promoted properties and all-optional
 * constructor arguments, so a request can be built incrementally (`new SalesOrderRequest()`, then
 * assign fields / append order lines) or in one named-argument call. There is no construction-time
 * validation — required fields are enforced by the Shipmondo API (a missing one surfaces as a
 * `ValidationException`).
 */
abstract class Payload
{
}
