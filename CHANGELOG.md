# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] — 2026-05-17

### Added

- Initial release scoped to the Contacts API.
- `EmsClient` entry point with lazy namespaced resources.
- `ContactsResource` covering `list`, `iterate`, `get`, `create`, `update`,
  `upsert`, `delete`, `deleteByEmail` with named-argument shortcuts.
- Typed DTOs (`Contact`, `ContactList`, `ContactGroup`, `ContactTag`,
  `UpsertResult`, pagination wrappers).
- Request DTOs (`CreateContactRequest`, `UpdateContactRequest`,
  `UpsertContactRequest`, `ListContactsQuery`).
- Domain enums (`ContactStatus`, `ContactGender`, `SyncMode`).
- Exception hierarchy with per-status mapping
  (`ValidationException::errors()`, `RateLimitException::retryAfter()`).
- PSR-18 transport with Guzzle 7 as the default; `php-http/discovery` for
  PSR-17 factories.
- `ObjectSerializer` handling `snake_case ↔ camelCase`, enums, dates and
  nested models.
- 100% offline PHPUnit suite using a small custom mock PSR-18 client.
