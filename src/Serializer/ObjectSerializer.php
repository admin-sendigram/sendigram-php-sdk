<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Serializer;

use BackedEnum;
use DateTimeImmutable;
use Sendigram\Ems\Client\Exception\SerializationException;
use Sendigram\Ems\Client\Model\Contact;
use Sendigram\Ems\Client\Model\ContactGroup;
use Sendigram\Ems\Client\Model\ContactList;
use Sendigram\Ems\Client\Model\ContactTag;
use Sendigram\Ems\Client\Model\PaginationLinks;
use Sendigram\Ems\Client\Model\PaginationMeta;
use Sendigram\Ems\Client\Model\UpsertResult;

/**
 * Translates between EMS JSON payloads (snake_case, scalar/string dates,
 * integer enum values) and SDK DTOs (camelCase, native types, enum cases).
 *
 * Centralising this here keeps Resource classes and Model classes free of
 * mapping concerns and gives a single place to test EMS payload shapes.
 */
final class ObjectSerializer
{
    /**
     * Build a typed DTO from a JSON-decoded array.
     *
     * @template T of object
     *
     * @param array<string, mixed> $data
     * @param class-string<T> $class
     *
     * @return T
     */
    public function deserialize(array $data, string $class): object
    {
        $obj = match ($class) {
            Contact::class => $this->buildContact($data),
            ContactGroup::class => new ContactGroup((int) $data['id'], (string) $data['name']),
            ContactTag::class => new ContactTag((int) $data['id'], (string) $data['name']),
            ContactList::class => $this->buildContactList($data),
            PaginationMeta::class => $this->buildMeta($data),
            PaginationLinks::class => $this->buildLinks($data),
            UpsertResult::class => throw new SerializationException('UpsertResult is built by ResponseParser, not deserialized directly'),
            default => throw new SerializationException("Cannot deserialize unknown class: {$class}"),
        };

        \assert($obj instanceof $class);

        return $obj;
    }

    /**
     * Convert a request DTO into a JSON-ready array. Null fields are omitted
     * so PUT-style "patch" semantics work without explicit nulls in JSON.
     *
     * @return array<string, mixed>
     */
    public function serialize(object $request): array
    {
        $out = [];
        $vars = get_object_vars($request);
        foreach ($vars as $name => $value) {
            if (null === $value) {
                continue;
            }
            $out[$this->snakeCase($name)] = $this->scalarize($value);
        }

        return $out;
    }

    /**
     * Convert a query DTO into URL query-string parameters. Same rules as
     * {@see serialize()} (null skipped, enums to values, dates to strings).
     *
     * @return array<string, scalar>
     */
    public function queryParams(object $query): array
    {
        $out = [];
        foreach (get_object_vars($query) as $name => $value) {
            if (null === $value) {
                continue;
            }
            $scalar = $this->scalarize($value);
            if (!is_scalar($scalar)) {
                throw new SerializationException("Query parameter '{$name}' is not scalar after serialization");
            }
            $out[$this->snakeCase($name)] = $scalar;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildContact(array $data): Contact
    {
        return new Contact(
            id: isset($data['id']) ? (int) $data['id'] : throw new SerializationException('Contact payload missing required field: id'),
            email: $this->stringOrNull($data['email'] ?? null),
            firstName: $this->stringOrNull($data['first_name'] ?? null),
            lastName: $this->stringOrNull($data['last_name'] ?? null),
            gender: $this->enumOrNull(
                $this->unwrapEnumLikeValue($data['gender'] ?? null),
                \Sendigram\Ems\Client\Model\Enum\ContactGender::class,
            ),
            status: $this->enumOrNull(
                $this->unwrapEnumLikeValue($data['status'] ?? null),
                \Sendigram\Ems\Client\Model\Enum\ContactStatus::class,
            ),
            dateOfBirth: $this->dateOrNull($data['date_of_birth'] ?? null, 'Y-m-d'),
            countryId: $this->intOrNull($data['country_id'] ?? null),
            cityId: $this->intOrNull($data['city_id'] ?? null),
            groups: $this->relationOrNull($data['groups'] ?? null, ContactGroup::class, array_key_exists('groups', $data)),
            tags: $this->relationOrNull($data['tags'] ?? null, ContactTag::class, array_key_exists('tags', $data)),
            createdAt: $this->date($data['created_at'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildContactList(array $data): ContactList
    {
        if (!array_key_exists('data', $data) || !is_array($data['data'])) {
            throw new SerializationException('ContactList payload missing data block');
        }

        $items = [];
        foreach ($data['data'] as $row) {
            if (!is_array($row)) {
                throw new SerializationException('ContactList.data contains non-array item');
            }
            $items[] = $this->buildContact($row);
        }

        if (!isset($data['meta']) || !is_array($data['meta'])) {
            throw new SerializationException('ContactList payload missing meta block');
        }
        if (!isset($data['links']) || !is_array($data['links'])) {
            throw new SerializationException('ContactList payload missing links block');
        }

        return new ContactList(
            data: $items,
            meta: $this->buildMeta($data['meta']),
            links: $this->buildLinks($data['links']),
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function buildMeta(array $meta): PaginationMeta
    {
        return new PaginationMeta(
            currentPage: (int) ($meta['current_page'] ?? 0),
            lastPage: (int) ($meta['last_page'] ?? 0),
            perPage: (int) ($meta['per_page'] ?? 0),
            total: (int) ($meta['total'] ?? 0),
            from: $this->intOrNull($meta['from'] ?? null),
            to: $this->intOrNull($meta['to'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $links
     */
    private function buildLinks(array $links): PaginationLinks
    {
        return new PaginationLinks(
            first: $this->stringOrNull($links['first'] ?? null),
            last: $this->stringOrNull($links['last'] ?? null),
            prev: $this->stringOrNull($links['prev'] ?? null),
            next: $this->stringOrNull($links['next'] ?? null),
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T[]|null
     */
    private function relationOrNull(mixed $value, string $class, bool $keyExists): ?array
    {
        if (!$keyExists || null === $value) {
            return null;
        }
        if (!is_array($value)) {
            throw new SerializationException('Relation must be array or null, got '.gettype($value));
        }
        $out = [];
        foreach ($value as $row) {
            if (!is_array($row)) {
                throw new SerializationException('Relation row must be an array');
            }
            $out[] = $this->deserialize($row, $class);
        }

        return $out;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!is_string($value)) {
            throw new SerializationException('Expected string, got '.gettype($value));
        }

        return $value;
    }

    private function intOrNull(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            throw new SerializationException('Expected int, got '.gettype($value));
        }

        return (int) $value;
    }

    /**
     * EMS sometimes wraps enum-typed fields as `['value' => N, 'label' => '...']`
     * (Laravel cast). Unwrap if so; otherwise return as-is.
     */
    private function unwrapEnumLikeValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param class-string<TEnum> $class
     *
     * @return TEnum|null
     */
    private function enumOrNull(mixed $value, string $class): ?\BackedEnum
    {
        if (null === $value) {
            return null;
        }
        try {
            /** @var TEnum $case */
            $case = $class::from($value);
        } catch (\ValueError|\TypeError $e) {
            throw new SerializationException("Unknown value '{$value}' for enum {$class}", 0, $e);
        }

        return $case;
    }

    private function dateOrNull(mixed $value, string $format): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }
        if (!is_string($value)) {
            throw new SerializationException('Expected date string, got '.gettype($value));
        }
        $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
        if (false === $date) {
            throw new SerializationException("Invalid date '{$value}' for format '{$format}'");
        }

        return $date;
    }

    private function date(mixed $value): \DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            throw new SerializationException('Required datetime field is missing');
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $e) {
            throw new SerializationException("Invalid datetime '{$value}'", 0, $e);
        }
    }

    /**
     * Reduce a property value to a JSON-encodable scalar (or array/null).
     *
     * NOTE: `DateTimeImmutable` is always formatted as `Y-m-d`. Any future
     * timestamp-typed request field must pre-format itself as a string before
     * reaching this helper.
     */
    private function scalarize(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value->format('Y-m-d');
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_scalar($value) || null === $value) {
            return $value;
        }
        throw new SerializationException('Cannot scalarize value of type '.get_debug_type($value));
    }

    private function snakeCase(string $camel): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_$0', $camel));
    }
}
