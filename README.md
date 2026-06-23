# Shipmondo PHP SDK

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

Consume the [Shipmondo API](https://app.shipmondo.com/api/public/v3/specification#/) in PHP.

## Installation

```bash
composer require setono/shipmondo-php-sdk
```

> Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md) — 2.x is a rewrite with a number of breaking changes.

## Usage

```php
<?php

use Setono\Shipmondo\Client\Client;

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Client('api_username', 'api_key');

$paymentGateways = $client
    ->paymentGateways()
    ->getPage()
;

foreach ($paymentGateways as $paymentGateway) {
    print_r($paymentGateway);
}
```

To target the sandbox API instead of production, pass `sandbox: true` to the constructor:

```php
$client = new Client('api_username', 'api_key', sandbox: true);
```

will output something:

```text
Setono\Shipmondo\Response\PaymentGateway\PaymentGateway Object
(
    [id] => 1234
    [name] => quickpay
    [provider] => quick_pay
    [merchantNumber] => 67894321
)
```

## Receiving webhooks

Shipmondo delivers webhooks as a `POST` whose body is `{"data": "<JWT>"}`, where the JWT is
HS256-signed with the `key` you chose when you created the webhook. Verifying that signature is the
only way to prove a request really came from Shipmondo, so the SDK ships a `WebhookParser` that does
it for you and hands back a typed `WebhookEvent`:

> **Webhook keys must be at least 32 bytes.** HS256 requires a key of at least 256 bits / 32 bytes
> (RFC 7518 §3.2). The SDK enforces this both when you create a webhook (`WebhookRequest`) and when
> you verify one (`WebhookParser`), so make sure the `key` you set on the webhook is long enough — a
> shorter key cannot be verified.

```php
<?php

use Setono\Shipmondo\Exception\MalformedWebhookException;
use Setono\Shipmondo\Exception\WebhookVerificationException;
use Setono\Shipmondo\Webhook\WebhookParser;

// $request is a PSR-7 ServerRequestInterface (from your framework / PSR-7 bridge).
// $key is the key you set on the webhook when creating it via $client->webhooks()->create(...).

try {
    $event = (new WebhookParser())->parse($request, $key);
} catch (WebhookVerificationException) {
    // Forged request or wrong key — do NOT process it.
    http_response_code(403);
    return;
} catch (MalformedWebhookException) {
    http_response_code(400);
    return;
}

$event->action;       // 'create', 'cancel', 'status_update', ... (the SMD-Action header)
$event->resourceType; // 'Shipments', 'Orders', ... (the SMD-Resource-Type header)
$event->resourceId;   // int|null (the SMD-Resource-Id header)
$event->data;         // array<string, mixed> — the resource, snake_case, as in the API docs
$event->data['id'];

// Reply within 3 seconds with a 200, then do the heavy lifting out of band.
http_response_code(200);
```

If you are not on PSR-7, pass the raw body and headers instead:

```php
$event = (new WebhookParser())->parsePayload($rawBody, $headers, $key);
```

Both methods pin the HS256 algorithm, so a token presenting any other `alg` (including `none`) is
rejected. The webhook `key` is passed per call, so a server that hosts several webhooks can read the
`SMD-Webhook-Id` header to pick the right key before verifying.

`WebhookParser` implements `WebhookParserInterface`, so you can type-hint the interface in your
controllers/services and inject the parser (or a mock) via your DI container.

## Production usage

Internally this library uses the [CuyZ/Valinor](https://github.com/CuyZ/Valinor) library which is particularly well suited
for turning API responses into DTOs (and request DTOs into JSON). However, this library has some overhead and works best
with a cache enabled.

The `Client` is immutable: configure a cached mapper/normalizer and inject them through the constructor. Use the static
helpers so the SDK's required configuration (date formats, superfluous-key handling, and the request null-stripping /
snake_case transformers) is applied to your cached builders:

```php
<?php

use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\NormalizerBuilder;
use Setono\Shipmondo\Client\Client;

require_once '../vendor/autoload.php';

$cache = new FileSystemCache('path/to/cache-directory');

$mapperBuilder = Client::configureMapperBuilder((new MapperBuilder())->withCache($cache));
$normalizerBuilder = Client::registerNormalizerTransformers((new NormalizerBuilder())->withCache($cache));

$client = new Client(
    'API_USERNAME',
    'API_KEY',
    mapperBuilder: $mapperBuilder,
    normalizerBuilder: $normalizerBuilder,
);
```

You can read more about it here: [Valinor: Performance and caching](https://valinor.cuyz.io/latest/other/performance-and-caching/).

## Notes

### Accessing fields the SDK doesn't model yet

Every response object exposes a `->raw` property containing the full decoded response with its
original snake_case keys (the same names as the Shipmondo API docs). Use it to reach fields the SDK
doesn't type yet:

```php
$order = $client->salesOrders()->getById(123);
$order->id;                  // typed property
$order->raw['order_status']; // any field, straight from the API payload
```

### Sales orders are eventually consistent

A sales order you just created via `salesOrders()->create()` may not appear in `salesOrders()->getPage()` /
`paginate()` immediately — Shipmondo indexes the list asynchronously, so there can be a short delay before a new
order is listed. The order is available straight away by id, so for read-after-write use the id returned by `create()`:

```php
$created = $client->salesOrders()->create($request);
$order = $client->salesOrders()->getById($created->id); // available immediately
```

[ico-version]: https://poser.pugx.org/setono/shipmondo-php-sdk/v/stable
[ico-license]: https://poser.pugx.org/setono/shipmondo-php-sdk/license
[ico-github-actions]: https://github.com/Setono/shipmondo-php-sdk/actions/workflows/build.yaml/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/shipmondo-php-sdk/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2Fshipmondo-php-sdk%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/shipmondo-php-sdk
[link-github-actions]: https://github.com/Setono/shipmondo-php-sdk/actions
[link-code-coverage]: https://codecov.io/gh/Setono/shipmondo-php-sdk
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/shipmondo-php-sdk/master
