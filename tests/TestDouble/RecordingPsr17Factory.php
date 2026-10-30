<?php

declare(strict_types=1);

namespace Setono\Shipmondo\TestDouble;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-17 request + stream factory that delegates to Nyholm and records what it was asked to
 * create, so a test can prove the Client used the factories it was given (rather than discovering
 * its own).
 */
final class RecordingPsr17Factory implements RequestFactoryInterface, StreamFactoryInterface
{
    /** @var list<string> "METHOD uri" of every request created */
    public array $createdRequests = [];

    /** @var list<string> the content of every stream created */
    public array $createdStreams = [];

    private readonly Psr17Factory $inner;

    public function __construct()
    {
        $this->inner = new Psr17Factory();
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        $this->createdRequests[] = sprintf('%s %s', $method, (string) $uri);

        return $this->inner->createRequest($method, $uri);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        $this->createdStreams[] = $content;

        return $this->inner->createStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return $this->inner->createStreamFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->inner->createStreamFromResource($resource);
    }
}
