<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Base class for exceptions thrown while verifying or parsing an INCOMING webhook — a request
 * Shipmondo POSTs to the consumer's server (as opposed to a response the SDK fetched from the API).
 *
 * Because the failure is in a request the consumer received, these exceptions carry no Shipmondo
 * HTTP response and therefore do NOT extend {@see ResponseAwareException}. Catch this base to
 * handle every webhook-parsing failure; catch the concrete subclasses
 * ({@see WebhookVerificationException} / {@see MalformedWebhookException}) to map them to the
 * appropriate HTTP status code in the response back to Shipmondo.
 */
abstract class WebhookException extends \RuntimeException implements ShipmondoException
{
}
