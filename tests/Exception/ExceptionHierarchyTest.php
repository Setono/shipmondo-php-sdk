<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExceptionHierarchyTest extends TestCase
{
    /**
     * @param class-string $class
     * @param class-string $expectedBase
     */
    #[Test]
    #[DataProvider('hierarchy')]
    public function it_has_the_expected_hierarchy(string $class, string $expectedBase): void
    {
        self::assertTrue(is_a($class, $expectedBase, true));
    }

    #[Test]
    public function it_exposes_the_error_message_from_the_body(): void
    {
        $exception = new ValidationException(new Response(422), body: '{"error":"Invalid or not found parameter(s)"}');

        self::assertSame('Invalid or not found parameter(s)', $exception->getError());
    }

    #[Test]
    public function it_returns_null_error_for_a_non_json_body(): void
    {
        $exception = new UnexpectedStatusCodeException(new Response(418), body: 'teapot');

        self::assertNull($exception->getError());
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function hierarchy(): \Generator
    {
        yield 'unauthorized is a client error' => [UnauthorizedException::class, ClientErrorException::class];
        yield 'not found is a client error' => [NotFoundException::class, ClientErrorException::class];
        yield 'validation is a client error' => [ValidationException::class, ClientErrorException::class];
        yield 'too many requests is a client error' => [TooManyRequestsException::class, ClientErrorException::class];
        yield 'internal server error is a server error' => [InternalServerErrorException::class, ServerErrorException::class];
        yield 'mapping is a malformed response' => [MappingException::class, MalformedResponseException::class];
        yield 'client error is response aware' => [ClientErrorException::class, ResponseAwareException::class];
        yield 'unauthorized is a shipmondo exception' => [UnauthorizedException::class, ShipmondoException::class];
        yield 'internal server error is a shipmondo exception' => [InternalServerErrorException::class, ShipmondoException::class];
        yield 'mapping is a shipmondo exception' => [MappingException::class, ShipmondoException::class];
        yield 'unexpected status code is a shipmondo exception' => [UnexpectedStatusCodeException::class, ShipmondoException::class];
        yield 'invalid url is a shipmondo exception' => [InvalidUrlException::class, ShipmondoException::class];
    }
}
