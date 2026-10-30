<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\Shipmondo\TestDouble\NonSeekableStream;

final class ResponseAwareExceptionTest extends TestCase
{
    #[Test]
    public function the_default_message_has_the_status_the_sanitized_request_and_the_trimmed_body(): void
    {
        $request = (new Psr17Factory())->createRequest('GET', 'https://app.shipmondo.com/api/public/v3/sales_orders?api_key=secret#frag');

        $exception = new ValidationException(new Response(422), body: "{\"error\":\"boom\"}\n", request: $request);

        self::assertSame(
            'The status code was: 422. [GET https://app.shipmondo.com/api/public/v3/sales_orders] The body was: {"error":"boom"}.',
            $exception->getMessage(),
        );
        self::assertSame(0, $exception->getCode());
    }

    #[Test]
    public function the_default_message_omits_an_empty_body(): void
    {
        $exception = new NotFoundException(new Response(404), body: " \n");

        self::assertSame('The status code was: 404.', $exception->getMessage());
    }

    #[Test]
    public function the_default_message_reads_the_response_body_when_none_is_supplied(): void
    {
        $exception = new InternalServerErrorException(new Response(500, [], 'Bad gateway'));

        self::assertSame('The status code was: 500. The body was: Bad gateway.', $exception->getMessage());
    }

    #[Test]
    public function the_default_message_prefers_the_supplied_body_over_a_drained_stream(): void
    {
        // The SDK pre-reads the body; on a non-seekable stream a second read would yield "".
        $stream = new NonSeekableStream('{"error":"boom"}');
        $body = (string) $stream;

        $exception = new ValidationException(new Response(422, [], $stream), body: $body);

        self::assertSame('The status code was: 422. The body was: {"error":"boom"}.', $exception->getMessage());
        self::assertSame('boom', $exception->getError());
    }

    #[Test]
    public function a_custom_message_and_previous_exception_are_kept_as_given(): void
    {
        $previous = new \RuntimeException('cause');

        $exception = new MalformedResponseException(new Response(200, [], 'whatever'), 'Custom message', $previous);

        self::assertSame('Custom message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function it_parses_a_lazily_read_body_once_so_repeated_calls_work_on_a_non_seekable_stream(): void
    {
        // A custom message means the constructor never touches the stream, so getError() is the one
        // (and only) reader of this read-once stream.
        $exception = new NotFoundException(new Response(404, [], new NonSeekableStream('{"error":"gone"}')), 'No resource found.');

        self::assertSame('gone', $exception->getError());
        self::assertSame('gone', $exception->getError());
    }

    #[Test]
    public function it_returns_null_error_for_a_whitespace_only_body(): void
    {
        $exception = new ValidationException(new Response(422), body: " \n");

        self::assertNull($exception->getError());
    }
}
