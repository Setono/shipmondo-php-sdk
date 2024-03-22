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
    ->get()
;

foreach ($paymentGateways as $paymentGateway) {
    print_r($paymentGateway);
}
```

will output something:

```text
Setono\Shipmondo\Response\PaymentGateways\PaymentGateway Object
(
    [id] => 1234
    [name] => quickpay
    [provider] => quick_pay
    [merchantNumber] => 67894321
)
```

## Production usage

Internally this library uses the [CuyZ/Valinor](https://github.com/CuyZ/Valinor) library which is particularly well suited
for turning API responses into DTOs. However, this library has some overhead and works best with a cache enabled.

When you instantiate the `Client` use the opportunity to set a cache:

```php
<?php

use CuyZ\Valinor\Cache\FileSystemCache;use Setono\Shipmondo\Client\Client;use Setono\Shipmondo\DTO\Box;

require_once '../vendor/autoload.php';

$cache = new FileSystemCache('path/to/cache-directory');
$client = new Client('API_USERNAME', 'API_KEY');
$client->getMapperBuilder()->withCache($cache);
```

You can read more about it here: [Valinor: Performance and caching](https://valinor.cuyz.io/1.3/other/performance-and-caching/).

[ico-version]: https://poser.pugx.org/setono/shipmondo-php-sdk/v/stable
[ico-license]: https://poser.pugx.org/setono/shipmondo-php-sdk/license
[ico-github-actions]: https://github.com/Setono/shipmondo-php-sdk/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/shipmondo-php-sdk/branch/master/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2Fshipmondo-php-sdk%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/shipmondo-php-sdk
[link-github-actions]: https://github.com/Setono/shipmondo-php-sdk/actions
[link-code-coverage]: https://codecov.io/gh/Setono/shipmondo-php-sdk
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/shipmondo-php-sdk/master
