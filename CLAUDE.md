# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A PHP SDK for the [Shipmondo API v3](https://app.shipmondo.com/api/public/v3/specification). Published as `setono/shipmondo-php-sdk`, namespace `Setono\Shipmondo\` (PSR-4, both `src/` and `tests/`). Requires PHP >=8.1. It is HTTP-client agnostic: it depends only on PSR-7/17/18 interfaces and uses `php-http/discovery` to auto-discover a concrete client (e.g. `kriswallsmith/buzz` + `nyholm/psr7` in dev).

The architecture deliberately mirrors the sibling SDK `setono/economic-php-sdk` (a local clone usually lives at `../economic-php-sdk`) — base-class endpoints, `Payload` requests serialized through Valinor's Normalizer, `Resource` responses carrying `$raw`, and a typed exception hierarchy. When in doubt about a pattern, that SDK is the reference. The one place Shipmondo diverges is pagination (see below).

## Commands

Composer scripts (`composer.json`):

- `composer analyse` — **PHPStan at level `max`** (config `phpstan.neon.dist`, which includes Valinor's two PHPStan extensions; needs `cuyz/valinor` 2.x).
- `composer check-style` / `composer fix-style` — ECS (`sylius-labs/coding-standard`).
- `composer phpunit` — PHPUnit 10 test suite (attribute-based: `#[Test]`, `#[DataProvider]`, `#[CoversClass]`).
- `composer rector` — Rector (`LevelSetList::UP_TO_PHP_81`); `src/Response` and `src/Request` are skipped from `ReadOnlyPropertyRector`.

Run a single test: `vendor/bin/phpunit --filter it_creates_a_sales_order` or `vendor/bin/phpunit tests/Client/Endpoint/SalesOrdersEndpointTest.php`.

Mutation testing: `vendor/bin/infection` (config `infection.json.dist`; thresholds minMsi 50 / minCoveredMsi 60 — ratchet up as coverage improves). pcov is the local coverage driver.

The user's shell aliases: `ca` (analyse), `cf` (fix-style), `cfca` (fix then analyse), `phpunit`, `infection`, `rector`.

### Dependency constraints that are load-bearing
- `cuyz/valinor: "^2.2.2 <2.4"` — **2.4 dropped PHP 8.1**; the upper bound keeps the 8.1 floor installable. The CI 8.1 lowest/highest matrix guards this.
- `phpunit/phpunit: "^10.5"` — 11/12 require PHP ≥8.2.
- PHPStan stack is wired directly (`phpstan/phpstan ^2.1` + strict-rules / phpunit / webmozart-assert + extension-installer); `setono/code-quality-pack` v3 is **not** usable here (requires PHP ≥8.2).

CI (`.github/workflows/build.yaml`): coding-standards + `composer validate --strict` + `composer normalize --dry-run` on 8.1; dependency-analysis, PHPStan (`composer analyse`), PHPUnit across 8.1/8.2/8.3 (lowest & highest); coverage + mutation on 8.3.

## Architecture

**`Client` (`src/Client/Client.php`) is the entry point and is immutable.** All collaborators (`httpClient`, `requestFactory`, `streamFactory`, `mapperBuilder`, `normalizerBuilder`) are constructor-injected (PSR-18/17 auto-discovered when omitted); there are no setters. Credentials are constructor args (`apiUser`, `apiKey`) → HTTP Basic auth on every request. Sandbox vs production is the `bool $sandbox = false` constructor arg (hosts `app.shipmondo.com` / `sandbox.shipmondo.com`, prefix `/api/public/v3`). It exposes one memoized accessor per endpoint (`paymentGateways()`, `pickupPoints()`, `salesOrders()`, `shipmentTemplates()`, `webhooks()`).

Layers, top to bottom:

1. **Endpoints** (`src/Client/Endpoint/`). Base `Endpoint` holds the client + `MapperBuilder` and provides `mapItem()` (the map+stamp pipeline). `ResourceEndpoint<T>` adds `getOne()` / `createOne()`; `CollectionEndpoint<T>` adds `getPage()` / `paginate()` / `getByQueryId()`. Concrete leaves declare `getPath()` + `getItemClass()` and expose only the operations that resource supports. **There are no `*EndpointInterface` types or capability traits** — operations are plain methods on the leaves, tested via a fake PSR-18 client (`tests/TestDouble/ScriptedHttpClient.php`), not mocks. To add an endpoint: extend `CollectionEndpoint<T>` (or `Endpoint`), implement `getPath()`/`getItemClass()`, add the typed public methods, and wire a memoized accessor into `Client` + `ClientInterface`.

2. **`Client::request()` / `get()` / `post()` / `delete()`** — low-level HTTP. `get`/`post`/`delete` return the **decoded JSON array**; `request()` is the PSR-7 escape hatch. `resolveUrl()` guards against sending auth credentials to a non-Shipmondo host or non-default port. `post()` serializes a `Payload` via the Normalizer.

3. **DTO mapping (CuyZ/Valinor 2.x).**
   - **Read path:** Shipmondo is snake_case, so `Endpoint::mapItem()` recursively camelCases the decoded keys (`camelCaseKeys()`), maps via Valinor, then stamps `$raw`. `$raw` is therefore **camelCased** (it lines up with the typed property names). The mapper (`Client::configureMapperBuilder`) uses `allowSuperfluousKeys()` (+ scalar casting, date formats), so unknown API fields are ignored.
   - **`$raw` is stamped by `RawStamper`, NOT a Valinor converter** — registering a converter leaks reflection per node (CuyZ/Valinor#800). Do not move it into `registerConverter()`. `RawStamper::stamp()` recurses the mapped graph, stamping each nested `Resource` with its slice (matched by property name / list position).
   - **Write path:** `Client::registerNormalizerTransformers()` registers two transformers — `\DateTimeInterface` → DATE_ATOM, and a `Payload` transformer that camelCase→snake_cases keys and strips `null`/`[]`. Backed enums normalize to their `->value` automatically.

### Pagination is header-based (the main divergence from economic-php-sdk)
Shipmondo list endpoints return a **bare JSON array** of items and carry pagination in **response headers** (`X-Current-Page`, `X-Per-Page`, `X-Total-Count`, `X-Total-Pages`). So `Collection` (`src/Response/Collection/Collection.php`) is **never Valinor-mapped** — `CollectionEndpoint::mapPage()` maps each item individually and reads the headers off `client->getLastResponse()`. `paginate()` increments `page` until an empty page or `page >= totalPages`.

`pickupPoints` is special: `GET /pickup_points` returns a bare array with **no** pagination headers, so `PickupPointsEndpoint` extends `Endpoint` (not `CollectionEndpoint`) and `search()` returns a plain `list<PickupPoint>`. `PaymentGateways` and `ShipmentTemplates` look items up by `?id=` query param (`getByQueryId()`), not a REST sub-path; `SalesOrders::getById()` uses the REST path `/sales_orders/{id}`.

## Requests, responses, exceptions

- **Requests** (`src/Request/`): `abstract Payload` is the marker the normalizer matches. Concrete DTOs are `final` with `public readonly` promoted properties; API-required fields are non-nullable (fail-fast `Assert` guards) and ordered first. Native enums live in `src/Enum/`. `PickupPointSearch` and `CollectionRequestOptions` are **query VOs (not Payloads)** with a `toArray()` of snake_case query params. Quirk: address `zipcode` (lowercase property name so snake-casing is a no-op) and the `vat_no` (ship_to/bill_to → `Recipient`) vs `vat_id` (sender → `Sender`) split.
- **Responses** (`src/Response/`): `abstract Resource` carries `public array $raw`. DTOs are `final` with `public readonly` props (not `final readonly class` — `$raw` is mutated post-map; Rector skips them). Domain folders are singular (`Response/ShipmentTemplate/…`). Response `Webhook` keeps `action`/`resourceName` as plain strings (lenient reads); the request `Webhook` uses enums.
- **Exceptions** (`src/Exception/`): marker interface `ShipmondoException` → `ResponseAwareException` (pre-reads the body once so `getError()` works on non-seekable streams; Shipmondo errors are `{"error": "..."}`) → `ClientErrorException` / `ServerErrorException` → concretes. `Client::assertStatusCode()` maps status codes via `match` (401/404/422/429, `>=500`, default `UnexpectedStatusCodeException`); 2xx-but-unmappable → `MappingException` (extends `MalformedResponseException`).

## Testing conventions

- Tests mirror `src/`, extend `Setono\Shipmondo\ShipmondoTestCase` (provides `client(ScriptedHttpClient)` + `BASE`), and use PHPUnit **attributes** (`#[Test]`, `#[DataProvider]` with `public static` providers).
- Drive endpoints with `ScriptedHttpClient` (fake PSR-18 keyed by full URL, records sent requests + supports response headers) — no mocking framework, no endpoint interfaces.
- PHPStan max is strict: avoid `assertInstanceOf` on already-narrowed types, and `assertIsArray()` before indexing into `json_decode`/`$raw` (both are `mixed`-valued).
- **`LiveClientTest`** hits the real API and is skipped unless `SHIPMONDO_LIVE` is `1`/`true`, reading `SHIPMONDO_USERNAME` / `SHIPMONDO_API_KEY` from the env. Never commit credentials; never enable in CI.
