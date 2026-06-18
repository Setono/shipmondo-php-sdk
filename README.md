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
