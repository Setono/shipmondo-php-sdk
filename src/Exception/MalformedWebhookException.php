<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown when an incoming webhook is structurally invalid — before, or independently of, the
 * signature check: the body is not JSON, the `data` field is missing or not a string, or the JWT
 * is malformed / uses an algorithm other than HS256.
 *
 * Respond with `400`.
 */
final class MalformedWebhookException extends WebhookException
{
}
