<?php

declare(strict_types=1);

namespace Setono\Shipmondo\TestDouble;

use Psr\Http\Message\StreamInterface;

/**
 * A forward-only, read-once PSR-7 stream, like `php://input` or a network socket: its contents can
 * be read exactly once, after which every read (including `__toString()`) yields `""`, and any
 * attempt to seek or rewind throws.
 *
 * Parameters are deliberately untyped so the class satisfies both psr/http-message v1 (untyped)
 * and v2 (typed) — the `--prefer-lowest` CI matrix resolves v1.
 */
final class NonSeekableStream implements StreamInterface
{
    private int $position = 0;

    public function __construct(private readonly string $contents)
    {
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->contents);
    }

    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * @param int $offset
     * @param int $whence
     */
    public function seek($offset, $whence = \SEEK_SET): void
    {
        throw new \RuntimeException('The stream is not seekable.');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('The stream is not seekable.');
    }

    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @param string $string
     */
    public function write($string): int
    {
        throw new \RuntimeException('The stream is not writable.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    /**
     * @param int $length
     */
    public function read($length): string
    {
        $chunk = substr($this->contents, $this->position, $length);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        $rest = substr($this->contents, $this->position);
        $this->position = strlen($this->contents);

        return $rest;
    }

    /**
     * @param string|null $key
     */
    public function getMetadata($key = null): mixed
    {
        return null === $key ? [] : null;
    }
}
