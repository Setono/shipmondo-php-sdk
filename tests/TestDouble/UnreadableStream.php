<?php

declare(strict_types=1);

namespace Setono\Shipmondo\TestDouble;

use Psr\Http\Message\StreamInterface;

/**
 * The body of a response whose transfer fails while it is read, e.g. because it takes longer than the HTTP client's
 * time limit: every read throws PSR-7's `\RuntimeException`, as the stream of Symfony's Psr18Client then does
 * ("Unable to read stream contents: Idle timeout reached for ...").
 *
 * Parameters are deliberately untyped so the class satisfies both psr/http-message v1 (untyped)
 * and v2 (typed) — the `--prefer-lowest` CI matrix resolves v1.
 */
final readonly class UnreadableStream implements StreamInterface
{
    public function __construct(private string $error = 'Unable to read stream contents: Idle timeout reached')
    {
    }

    public function __toString(): string
    {
        // PSR-7 forbids __toString() to throw, so it says nothing about the failure
        return '';
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
        return 0;
    }

    public function eof(): bool
    {
        return false;
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
        throw new \RuntimeException($this->error);
    }

    public function getContents(): string
    {
        throw new \RuntimeException($this->error);
    }

    /**
     * @param string|null $key
     */
    public function getMetadata($key = null): mixed
    {
        return null === $key ? [] : null;
    }
}
