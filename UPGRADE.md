# Upgrade from 1.x to 2.x

2.x is a full rewrite with a cleaner, more type-safe architecture. The PHP requirement is unchanged
(`>=8.1`), but most of the public API changed. This guide lists every breaking change with
before/after examples.

## Dependencies

- `cuyz/valinor` was bumped from `^1.10` to `^2.2.2`.
- `psr/log` was **removed** — the SDK no longer logs. Wrap your PSR-18 client with logging middleware
  if you need it.

## Client construction

The `Client` is now **immutable**: all collaborators are constructor arguments and every setter was
removed.

```php
// 1.x
$client = new Client('username', 'api_key');
$client->setDebug(true);                 // sandbox
$client->setHttpClient($httpClient);
$client->setRequestFactory($requestFactory);
$client->setLogger($logger);
$client->getMapperBuilder()->withCache($cache);

// 2.x
$client = new Client(
    'api_user',
    'api_key',
    sandbox: true,                       // replaces setDebug()
    httpClient: $httpClient,             // optional; auto-discovered if omitted
    requestFactory: $requestFactory,
    streamFactory: $streamFactory,
    mapperBuilder: Client::configureMapperBuilder((new MapperBuilder())->withCache($cache)),
    normalizerBuilder: Client::registerNormalizerTransformers((new NormalizerBuilder())->withCache($cache)),
);
```

Removed: `setHttpClient()`, `setRequestFactory()`, `setLogger()`, `setDebug()`, `setMapperBuilder()`,
`getMapperBuilder()`.

## Low-level HTTP methods

`get()` / `post()` / `delete()` now return the **decoded JSON array** instead of a PSR-7
`ResponseInterface`. Use `request()` (unchanged) as the escape hatch for the raw response;
`getLastRequest()` / `getLastResponse()` are unchanged.

```php
// 1.x — returned ResponseInterface
$response = $client->get('sales_orders');

// 2.x — returns array
$data = $client->get('sales_orders');
```

`post()` now takes a typed `Setono\Shipmondo\Request\Payload` (was `array|object`).

## Endpoints

- The collection getter was renamed: **`->get(Query)` → `->getPage(?CollectionRequestOptions)`**, and
  there is a new `->paginate()` generator that walks all pages.
- All endpoint interfaces and capability traits were removed (`EndpointInterface`,
  `ReadableEndpointInterface`/`Trait`, `CreatableEndpointInterface`/`Trait`,
  `DeletableEndpointInterface`/`Trait`). Endpoints are concrete classes; type-hint the concrete class
  if needed.
- `pickupPoints()` no longer returns a `Collection` — Shipmondo returns a bare array with no
  pagination headers, so it is now **`->search(PickupPointSearch): list<PickupPoint>`**.
- `webhooks()->delete(int)` now returns `void` (was the deleted `Webhook`).

```php
// 1.x
$orders   = $client->salesOrders()->get();                 // Collection
$points   = $client->pickupPoints()->get($pickupPointsQuery);  // Collection

// 2.x
$orders   = $client->salesOrders()->getPage();             // Collection
foreach ($client->salesOrders()->paginate() as $order) { /* ... */ }
$points   = $client->pickupPoints()->search($pickupPointSearch);   // list<PickupPoint>
```

## Query / collection options

- `Request\Query\Query` and `Request\Query\CollectionQuery` were removed in favour of
  `Request\CollectionRequestOptions` (immutable; `new`, `withPage()`, `withPerPage()`).
- `Request\PickupPoints\PickupPointsCollectionQuery` → `Request\PickupPointSearch`.

```php
// 1.x
$query = (new CollectionQuery())->page(2)->perPage(50);
$client->salesOrders()->get($query);

// 2.x
$client->salesOrders()->getPage((new CollectionRequestOptions())->withPage(2)->withPerPage(50));
```

## Request DTOs

The base class `Request\Request` (a `JsonSerializable` with `filter()`) became `Request\Payload`
(a marker; serialization is handled centrally by the SDK's Valinor normalizer). Namespaces were
singularised and the top-level request bodies gained a `Request` suffix:

| 1.x | 2.x |
| --- | --- |
| `Request\SalesOrders\SalesOrder` | `Request\SalesOrder\SalesOrderRequest` |
| `Request\SalesOrders\Address` (ship_to / bill_to) | `Request\SalesOrder\Recipient` |
| `Request\SalesOrders\Address` (sender) | `Request\SalesOrder\Sender` |
| `Request\SalesOrders\OrderLine` | `Request\SalesOrder\OrderLine` |
| `Request\SalesOrders\PaymentDetails` | `Request\SalesOrder\PaymentDetails` |
| `Request\SalesOrders\ServicePoint` | `Request\SalesOrder\ServicePoint` |
| `Request\Webhooks\Webhook` | `Request\Webhook\WebhookRequest` |

Notable changes:

- The single `Address` is now two types: **`Recipient`** for `ship_to` / `bill_to` (uses `vat_no`,
  has `instruction`) and **`Sender`** for `sender` (uses `vat_id`).
- Class constants became native enums (`Setono\Shipmondo\Enum\…`): `OrderLine::LINE_TYPE_*` →
  `OrderLineType`, `Webhook::RESOURCE_*` → `WebhookResourceName`, the webhook action string →
  `WebhookAction`, the packing slip format string → `PackingSlipFormat`.
- Request body DTOs are **mutable with all-optional constructor arguments** (like 1.x): build them
  with named arguments, or incrementally by assigning properties and appending to `$orderLines`.
  There is no construction-time validation — required fields are enforced by the API (a missing one
  surfaces as a `ValidationException`).

```php
// 2.x — one named-argument call
$client->salesOrders()->create(new SalesOrderRequest(
    orderId: '27000',
    shipTo: new Recipient(name: 'Jane', address1: 'Main 1', city: 'CPH', zipcode: '1000', countryCode: 'DK'),
    paymentDetails: new PaymentDetails(amountIncludingVat: '125.0', vatAmount: '25.0', currencyCode: 'DKK'),
    orderLines: [new OrderLine(itemName: 'Widget', currencyCode: 'DKK', quantity: 1)],
));

// 2.x — or built incrementally (e.g. across services/events)
$request = new SalesOrderRequest();
$request->orderId = '27000';
$request->shipTo = new Recipient();
$request->shipTo->name = 'Jane';
$request->orderLines[] = new OrderLine(itemName: 'Widget', currencyCode: 'DKK');
$client->salesOrders()->create($request);
```

## Response DTOs

The base marker `Response\Response` became `Response\Resource`, which carries a new
`public array $raw` property — the full decoded response with its original snake_case keys, so you
can reach any field the SDK doesn't model yet. Namespaces were singularised:

| 1.x | 2.x |
| --- | --- |
| `Response\PaymentGateways\PaymentGateway` | `Response\PaymentGateway\PaymentGateway` |
| `Response\PickupPoints\PickupPoint` | `Response\PickupPoint\PickupPoint` |
| `Response\SalesOrders\SalesOrder` | `Response\SalesOrder\SalesOrder` |
| `Response\ShipmentTemplates\*` | `Response\ShipmentTemplate\*` |
| `Response\Webhooks\Webhook` | `Response\Webhook\Webhook` |
| `Response\Collection` | `Response\Collection\Collection` |

`Collection` changes:

- `first()` now returns `null` instead of `false` when empty.
- It gained a `totalCount` property (from the `X-Total-Count` header); it exposes
  `items`, `page`, `pageSize`, `totalCount`, `totalPages`.

```php
$order = $client->salesOrders()->getById(123);
$order->id;                       // typed
$order->raw['order_status'];      // any unmodeled field, snake_case
```

## Exceptions

- `NotAuthorizedException` (401) was renamed to `UnauthorizedException`.
- New marker interface `ShipmondoException` is implemented by every SDK exception — catch it to net
  them all.
- New types: `ValidationException` (422), `TooManyRequestsException` (429), plus the base abstracts
  `ClientErrorException` (4xx) and `ServerErrorException` (5xx), and `MalformedResponseException` /
  `MappingException` for unusable 2xx bodies.
- The per-exception static `assert()` methods were removed (status-code mapping is centralised in the
  `Client`).
- `ResponseAwareException::getError(): ?string` exposes Shipmondo's `{"error": "..."}` message.

```php
// 1.x
catch (NotAuthorizedException $e) { /* ... */ }

// 2.x
catch (UnauthorizedException $e) { /* ... */ }
catch (ShipmondoException $e) { echo $e->getError(); } // any SDK exception
```

## Resolver

`ShipmentTemplateResolverInterface` was removed — use `ShipmentTemplateResolver` directly. The
resolver logic and the `Shipment` value object are otherwise unchanged (only the response namespace
moved from `Response\ShipmentTemplates` to `Response\ShipmentTemplate`).
