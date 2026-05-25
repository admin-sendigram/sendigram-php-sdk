# Sendigram EMS PHP SDK

Official PHP SDK for the [Sendigram EMS](https://ems.sendigram.com) OpenAPI.

## Requirements

- PHP 8.1+
- ext-json
- A PSR-18 HTTP client (Guzzle 7 is installed by default)

## Installation

```bash
composer require sendigram/ems-php
```

## Quickstart

```php
use Sendigram\Ems\Client\EmsClient;

$client = new EmsClient('YOUR_API_TOKEN');

// List the first page of contacts
$list = $client->contacts->list(perPage: 50);
foreach ($list->data as $contact) {
    echo $contact->email, "\n";
}

// Fetch one
$contact = $client->contacts->get(42);

// Create
$created = $client->contacts->create(
    email:     'john@example.com',
    firstName: 'John',
    lastName:  'Doe',
);

// Update (only provided fields are touched)
$updated = $client->contacts->update(42, firstName: 'Jane');

// Upsert by email
$result = $client->contacts->upsert(email: 'alice@example.com', firstName: 'Alice');
$result->created;          // true on HTTP 201, false on HTTP 200
$result->contact;          // typed Contact

// Delete
$client->contacts->delete(42);
$client->contacts->deleteByEmail('john@example.com');

// Iterate every page lazily
foreach ($client->contacts->iterate(perPage: 100) as $contact) {
    // …
}
```

## Authentication

Issue a token from the EMS dashboard under **Account → API token** and pass it to
`EmsClient`. The SDK sends `Authorization: Bearer {token}` on every request.

## Configuration

```php
use Sendigram\Ems\Client\Configuration;
use Sendigram\Ems\Client\EmsClient;

$client = new EmsClient(
    Configuration::default('YOUR_API_TOKEN')
        ->withBaseUrl('https://ems.sendigram.com/open-api/v1/')
        ->withTimeout(60)
        ->withUserAgent('my-app/1.0'),
);
```

## Custom HTTP client

Any PSR-18 client works. Pass your own to bypass Guzzle:

```php
$client = new EmsClient(
    config:         Configuration::default('YOUR_API_TOKEN'),
    httpClient:     $mySymfonyHttpClient,
    requestFactory: $myRequestFactory,
    streamFactory:  $myStreamFactory,
);
```

## Error handling

Every SDK error extends `Sendigram\Ems\Client\Exception\EmsException`. HTTP errors are typed:

```php
use Sendigram\Ems\Client\Exception\NotFoundException;
use Sendigram\Ems\Client\Exception\RateLimitException;
use Sendigram\Ems\Client\Exception\ValidationException;

try {
    $client->contacts->create(email: 'not-an-email');
} catch (ValidationException $e) {
    foreach ($e->errors() as $field => $messages) {
        echo "{$field}: " . implode('; ', $messages), "\n";
    }
} catch (NotFoundException $e) {
    // 404
} catch (RateLimitException $e) {
    sleep($e->retryAfter() ?? 60);
}
```

| Status | Exception |
| ------ | --------- |
| 400 | `BadRequestException` |
| 401 | `UnauthorizedException` |
| 403 | `ForbiddenException` |
| 404 | `NotFoundException` |
| 422 | `ValidationException` (has `errors()`) |
| 429 | `RateLimitException` (has `retryAfter()`) |
| 5xx | `ServerException` |
| transport failure | `NetworkException` |
| body parse failure | `SerializationException` |

## Examples

See [`examples/`](examples) for runnable scripts.

## Versioning

This package follows [SemVer](https://semver.org). Currently targets EMS API `v1`.

## License

MIT — see [LICENSE](LICENSE).
