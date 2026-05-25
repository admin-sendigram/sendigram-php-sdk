# Contributing

## Development setup

```bash
git clone <repo>
cd ems-php
composer install
```

## Running tests

```bash
composer test            # PHPUnit
composer stan            # PHPStan (level 8)
composer cs              # php-cs-fixer (dry run)
composer cs-fix          # php-cs-fixer (apply)
```

The test suite is fully offline — there are no network calls. All HTTP
interactions go through `tests/Support/MockHttpClient.php` with canned
responses from `tests/Fixtures/responses/`.

## Adding a new EMS resource

The SDK is structured so new resources are a 3-file change:

1. **Resource class** — extend `Sendigram\Ems\Client\Resources\AbstractResource` and
   add one method per controller endpoint, mirroring the request/response
   shapes from EMS. Use `RequestBuilder` + `HttpTransport` + `ResponseParser`
   from the protected base properties.

2. **Models** — add typed DTOs under `src/Model/` for responses and
   `src/Model/Requests/` for inputs. Use `readonly` properties for response
   DTOs, mutable for request DTOs (users mutate them).

3. **Register** the new resource in `EmsClient::RESOURCE_MAP`.

Don't forget tests, fixtures, and a CHANGELOG entry.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/):
`feat:`, `fix:`, `docs:`, `test:`, `chore:`, …

## Releases

1. Update `CHANGELOG.md` under a new version heading.
2. Bump `Configuration::SDK_VERSION`.
3. Tag the commit: `git tag vX.Y.Z && git push --tags`.
4. Packagist webhook picks it up automatically.
