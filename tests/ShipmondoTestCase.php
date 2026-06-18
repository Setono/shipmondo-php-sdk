<?php

declare(strict_types=1);

namespace Setono\Shipmondo;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\Client\Client;
use Setono\Shipmondo\TestDouble\ScriptedHttpClient;

abstract class ShipmondoTestCase extends TestCase
{
    protected const BASE = 'https://sandbox.shipmondo.com/api/public/v3';

    protected function client(ScriptedHttpClient $http): Client
    {
        $psr17 = new Psr17Factory();

        return new Client('user', 'key', sandbox: true, httpClient: $http, requestFactory: $psr17, streamFactory: $psr17);
    }

    /**
     * Loads a real API response payload captured from the Shipmondo sandbox (see tests/Fixtures).
     */
    protected static function fixture(string $name): string
    {
        $contents = file_get_contents(__DIR__ . '/Fixtures/' . $name);
        if (false === $contents) {
            self::fail(sprintf('Fixture "%s" could not be read.', $name));
        }

        return $contents;
    }
}
