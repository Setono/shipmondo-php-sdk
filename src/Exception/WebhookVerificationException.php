<?php

declare(strict_types=1);

namespace Setono\Shipmondo\Exception;

/**
 * Thrown when an incoming webhook's HS256 signature could not be verified with the supplied key —
 * i.e. the request is forged or the wrong webhook key was used.
 *
 * Treat this as an authentication failure: do NOT process the payload, and respond with `401` or
 * `403`.
 */
final class WebhookVerificationException extends WebhookException
{
}
