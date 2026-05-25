<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Resources;

use Sendigram\Ems\Client\Model\Contact;
use Sendigram\Ems\Client\Model\ContactList;
use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;
use Sendigram\Ems\Client\Model\Requests\CreateContactRequest;
use Sendigram\Ems\Client\Model\Requests\ListContactsQuery;
use Sendigram\Ems\Client\Model\Requests\UpdateContactRequest;
use Sendigram\Ems\Client\Model\Requests\UpsertContactRequest;
use Sendigram\Ems\Client\Model\UpsertResult;

/**
 * Wraps the EMS Contacts API. One method per HTTP endpoint in EMS's
 * `ContactController`. Every method accepts either:
 *
 * - an explicit request DTO, or
 * - named arguments (the resource normalizes them into a DTO internally).
 *
 * Both styles produce identical HTTP requests.
 */
final class ContactsResource extends AbstractResource
{
    /**
     * `GET /contacts`.
     */
    public function list(
        ?ListContactsQuery $query = null,
        ?string $q = null,
        ?ContactStatus $status = null,
        ?int $perPage = null,
        ?int $page = null,
    ): ContactList {
        $query ??= new ListContactsQuery(
            q: $q,
            status: $status,
            perPage: $perPage ?? 20,
            page: $page ?? 1,
        );

        $request = $this->requestBuilder->build(
            method: 'GET',
            path: 'contacts',
            query: $this->serializer->queryParams($query),
        );

        $response = $this->transport->send($request);

        /** @var ContactList $list */
        $list = $this->responseParser->parse($response, ContactList::class, 'GET', (string) $request->getUri());

        return $list;
    }

    /**
     * Yields every contact across all pages by repeatedly calling `list()`.
     */
    public function iterate(?int $perPage = null): \Generator
    {
        $page = 1;
        $size = $perPage ?? 100;
        while (true) {
            $list = $this->list(perPage: $size, page: $page);
            foreach ($list->data as $contact) {
                yield $contact;
            }
            if (!$list->hasNextPage()) {
                return;
            }
            ++$page;
        }
    }

    /** `GET /contacts/{id}`. */
    public function get(int $id): Contact
    {
        $request = $this->requestBuilder->build('GET', "contacts/{$id}");
        $response = $this->transport->send($request);

        /** @var Contact $contact */
        $contact = $this->responseParser->parse($response, Contact::class, 'GET', (string) $request->getUri());

        return $contact;
    }

    /**
     * `POST /contacts`. Accepts either a DTO or named arguments.
     *
     * @param int[]|RelationOp|null $groups
     * @param int[]|RelationOp|null $tags
     * @param array<string, string>|null $fields
     */
    public function create(
        ?CreateContactRequest $request = null,
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?ContactGender $gender = null,
        ?\DateTimeImmutable $dateOfBirth = null,
        ?string $country = null,
        ?string $city = null,
        ?ContactStatus $status = null,
        ?SyncMode $mode = null,
        array|RelationOp|null $groups = null,
        array|RelationOp|null $tags = null,
        ?array $fields = null,
    ): Contact {
        if (null === $request) {
            if (null === $email) {
                throw new \InvalidArgumentException('create(): either a CreateContactRequest or a non-null email argument is required');
            }
            $request = new CreateContactRequest(
                email: $email,
                firstName: $firstName,
                lastName: $lastName,
                gender: $gender,
                dateOfBirth: $dateOfBirth,
                country: $country,
                city: $city,
                status: $status,
                mode: $mode ?? SyncMode::SYNC,
                groups: $groups,
                tags: $tags,
                fields: $fields,
            );
        }

        $http = $this->requestBuilder->build('POST', 'contacts', body: $this->serializer->serialize($request));
        $response = $this->transport->send($http);

        /** @var Contact $contact */
        $contact = $this->responseParser->parse($response, Contact::class, 'POST', (string) $http->getUri());

        return $contact;
    }

    /**
     * `PUT /contacts/{id}`. Accepts either a DTO or named arguments. All
     * fields are optional — only provided fields are sent.
     *
     * @param int[]|RelationOp|null $groups
     * @param int[]|RelationOp|null $tags
     * @param array<string, string>|null $fields
     */
    public function update(
        int $id,
        ?UpdateContactRequest $request = null,
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?ContactGender $gender = null,
        ?\DateTimeImmutable $dateOfBirth = null,
        ?string $country = null,
        ?string $city = null,
        ?ContactStatus $status = null,
        ?SyncMode $mode = null,
        array|RelationOp|null $groups = null,
        array|RelationOp|null $tags = null,
        ?array $fields = null,
    ): Contact {
        $request ??= new UpdateContactRequest(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            gender: $gender,
            dateOfBirth: $dateOfBirth,
            country: $country,
            city: $city,
            status: $status,
            mode: $mode,
            groups: $groups,
            tags: $tags,
            fields: $fields,
        );

        $http = $this->requestBuilder->build('PUT', "contacts/{$id}", body: $this->serializer->serialize($request));
        $response = $this->transport->send($http);

        /** @var Contact $contact */
        $contact = $this->responseParser->parse($response, Contact::class, 'PUT', (string) $http->getUri());

        return $contact;
    }

    /**
     * `POST /contacts/upsert`. Returns an {@see UpsertResult} so callers can
     * distinguish create (HTTP 201) from update (HTTP 200).
     *
     * @param int[]|RelationOp|null $groups
     * @param int[]|RelationOp|null $tags
     * @param array<string, string>|null $fields
     */
    public function upsert(
        ?UpsertContactRequest $request = null,
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?ContactGender $gender = null,
        ?\DateTimeImmutable $dateOfBirth = null,
        ?string $country = null,
        ?string $city = null,
        ?ContactStatus $status = null,
        ?SyncMode $mode = null,
        array|RelationOp|null $groups = null,
        array|RelationOp|null $tags = null,
        ?array $fields = null,
    ): UpsertResult {
        if (null === $request) {
            if (null === $email) {
                throw new \InvalidArgumentException('upsert(): either an UpsertContactRequest or a non-null email argument is required');
            }
            $request = new UpsertContactRequest(
                email: $email,
                firstName: $firstName,
                lastName: $lastName,
                gender: $gender,
                dateOfBirth: $dateOfBirth,
                country: $country,
                city: $city,
                status: $status,
                mode: $mode ?? SyncMode::SYNC,
                groups: $groups,
                tags: $tags,
                fields: $fields,
            );
        }

        $http = $this->requestBuilder->build('POST', 'contacts/upsert', body: $this->serializer->serialize($request));
        $response = $this->transport->send($http);

        [$contact, $httpStatus] = $this->responseParser->parseWithStatus(
            $response,
            Contact::class,
            'POST',
            (string) $http->getUri(),
        );

        return new UpsertResult($contact, 201 === $httpStatus);
    }

    /** `DELETE /contacts/{id}`. */
    public function delete(int $id): void
    {
        $http = $this->requestBuilder->build('DELETE', "contacts/{$id}");
        $response = $this->transport->send($http);

        $this->responseParser->parse($response, null, 'DELETE', (string) $http->getUri());
    }

    /**
     * `DELETE /contacts/by-email/{email}`.
     *
     * The email is interpolated into the request path. A literal `/` in the
     * email would be interpreted by the path encoder as a segment separator
     * and silently route the request to the wrong endpoint, so a slash is
     * rejected up-front. Real email addresses cannot contain `/`.
     *
     * @throws \InvalidArgumentException when the email contains a forward slash
     */
    public function deleteByEmail(string $email): void
    {
        if (str_contains($email, '/')) {
            throw new \InvalidArgumentException('deleteByEmail(): email must not contain a forward slash');
        }

        $http = $this->requestBuilder->build('DELETE', "contacts/by-email/{$email}");
        $response = $this->transport->send($http);

        $this->responseParser->parse($response, null, 'DELETE', (string) $http->getUri());
    }
}
