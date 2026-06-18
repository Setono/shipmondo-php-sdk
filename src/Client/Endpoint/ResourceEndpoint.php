<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Client\Endpoint;

use Setono\Shipmondo\Request\Payload;
use Setono\Shipmondo\Response\Resource;

/**
 * Abstract base for endpoints that represent a single REST resource at a path with a typed item DTO.
 *
 * Subclasses declare two protected hints: {@see self::getPath()} (the resource URL path) and
 * {@see self::getItemClass()} (the typed DTO class). Shared helpers:
 *  - {@see self::getOne()}    — GET `"{getPath()}"` or `"{getPath()}/{$id}"` + map + `$raw` stamp.
 *  - {@see self::createOne()} — POST a typed body + map + `$raw` stamp.
 *
 * @template T of Resource
 */
abstract class ResourceEndpoint extends Endpoint
{
    /**
     * @internal Implemented by SDK endpoint subclasses to declare their resource path. Not part of
     *           the package's BC promise; downstream subclassing is not supported.
     */
    abstract protected static function getPath(): string;

    /**
     * @internal Implemented by SDK endpoint subclasses. Not part of the package's BC promise.
     *
     * @return class-string<T>
     */
    abstract protected static function getItemClass(): string;

    /**
     * @param int|string|null $id when null, fetches `getPath()`; when given, fetches `"{getPath()}/{$id}"`
     *
     * @return T
     */
    protected function getOne(int|string|null $id = null): Resource
    {
        $path = null === $id
            ? static::getPath()
            : sprintf('%s/%s', static::getPath(), $id);

        return $this->mapItem(static::getItemClass(), $this->client->get($path));
    }

    /**
     * @return T
     */
    protected function createOne(Payload $request): Resource
    {
        return $this->mapItem(static::getItemClass(), $this->client->post(static::getPath(), $request));
    }
}
