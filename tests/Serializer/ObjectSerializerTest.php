<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Exception\SerializationException;
use Sendigram\Ems\Client\Model\Contact;
use Sendigram\Ems\Client\Model\ContactGroup;
use Sendigram\Ems\Client\Model\ContactList;
use Sendigram\Ems\Client\Model\ContactTag;
use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;
use Sendigram\Ems\Client\Model\PaginationLinks;
use Sendigram\Ems\Client\Model\PaginationMeta;
use Sendigram\Ems\Client\Model\Requests\CreateContactRequest;
use Sendigram\Ems\Client\Model\Requests\ListContactsQuery;
use Sendigram\Ems\Client\Model\Requests\UpdateContactRequest;
use Sendigram\Ems\Client\Serializer\ObjectSerializer;

final class ObjectSerializerTest extends TestCase
{
    private ObjectSerializer $s;

    protected function setUp(): void
    {
        $this->s = new ObjectSerializer();
    }

    public function testContactFromArrayBasicFields(): void
    {
        $c = $this->s->deserialize($this->contactPayload(), Contact::class);

        $this->assertInstanceOf(Contact::class, $c);
        $this->assertSame(42, $c->id);
        $this->assertSame('a@b.c', $c->email);
        $this->assertSame('John', $c->firstName);
        $this->assertSame('Doe', $c->lastName);
        $this->assertSame(7, $c->countryId);
        $this->assertSame(13, $c->cityId);
    }

    public function testContactFromArrayMapsEnums(): void
    {
        $c = $this->s->deserialize($this->contactPayload(), Contact::class);

        $this->assertSame(ContactGender::FEMALE, $c->gender);
        $this->assertSame(ContactStatus::ACTIVE, $c->status);
    }

    public function testContactFromArrayParsesDates(): void
    {
        $c = $this->s->deserialize($this->contactPayload(), Contact::class);

        $this->assertSame('1990-01-01', $c->dateOfBirth?->format('Y-m-d'));
        $this->assertSame('2026-01-02T03:04:05+00:00', $c->createdAt->format('c'));
    }

    public function testContactFromArrayParsesNestedRelations(): void
    {
        $c = $this->s->deserialize($this->contactPayload(), Contact::class);

        $this->assertNotNull($c->groups);
        $this->assertCount(1, $c->groups);
        $this->assertInstanceOf(ContactGroup::class, $c->groups[0]);
        $this->assertSame(1, $c->groups[0]->id);
        $this->assertSame('newsletter', $c->groups[0]->name);
    }

    public function testContactFromArrayTreatsMissingRelationsAsNull(): void
    {
        $payload = $this->contactPayload();
        unset($payload['groups'], $payload['tags']);

        $c = $this->s->deserialize($payload, Contact::class);

        $this->assertNull($c->groups);
        $this->assertNull($c->tags);
    }

    public function testContactFromArrayAcceptsEmptyArrayRelation(): void
    {
        $payload = $this->contactPayload();
        $payload['tags'] = [];

        $c = $this->s->deserialize($payload, Contact::class);

        $this->assertSame([], $c->tags);
    }

    public function testContactFromArrayIgnoresUnknownFields(): void
    {
        $payload = $this->contactPayload();
        $payload['mystery_meat'] = 'should be ignored';

        $c = $this->s->deserialize($payload, Contact::class);

        $this->assertSame(42, $c->id);
    }

    public function testContactListFromArray(): void
    {
        $payload = [
            'data' => [$this->contactPayload()],
            'meta' => [
                'current_page' => 1, 'last_page' => 3, 'per_page' => 20,
                'total' => 50, 'from' => 1, 'to' => 20,
            ],
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        ];

        $list = $this->s->deserialize($payload, ContactList::class);

        $this->assertInstanceOf(ContactList::class, $list);
        $this->assertCount(1, $list->data);
        $this->assertInstanceOf(Contact::class, $list->data[0]);
        $this->assertSame(1, $list->meta->currentPage);
        $this->assertSame(3, $list->meta->lastPage);
    }

    public function testCreateRequestToArrayUsesSnakeCase(): void
    {
        $req = new CreateContactRequest(
            email: 'a@b.c',
            firstName: 'A',
            lastName: 'B',
            gender: ContactGender::FEMALE,
            dateOfBirth: new \DateTimeImmutable('1990-01-01'),
            country: 'PL',
            city: 'Warsaw',
            status: ContactStatus::ACTIVE,
            mode: SyncMode::ATTACH,
            groups: [1, 2],
        );

        $out = $this->s->serialize($req);

        $this->assertSame([
            'email' => 'a@b.c',
            'first_name' => 'A',
            'last_name' => 'B',
            'gender' => 2,
            'date_of_birth' => '1990-01-01',
            'country' => 'PL',
            'city' => 'Warsaw',
            'status' => 1,
            'mode' => 'attach',
            'groups' => [1, 2],
        ], $out);
    }

    public function testCreateRequestToArrayOmitsNulls(): void
    {
        $req = new CreateContactRequest(email: 'a@b.c');

        $out = $this->s->serialize($req);

        $this->assertSame(['email' => 'a@b.c', 'mode' => 'sync'], $out);
    }

    public function testUpdateRequestToArrayOmitsAllNulls(): void
    {
        $req = new UpdateContactRequest(firstName: 'A');

        $out = $this->s->serialize($req);

        $this->assertSame(['first_name' => 'A'], $out);
    }

    public function testCreateRequestSerializesTagsAndFields(): void
    {
        $req = new CreateContactRequest(
            email: 'a@b.c',
            tags: [1, 2],
            fields: ['plan' => 'pro', 'birthday_gift' => 'mug'],
        );

        $out = $this->s->serialize($req);

        $this->assertSame([1, 2], $out['tags']);
        $this->assertSame(['plan' => 'pro', 'birthday_gift' => 'mug'], $out['fields']);
    }

    public function testDetachAllSentinelSerializesToEmptyString(): void
    {
        $req = new UpdateContactRequest(
            groups: RelationOp::DETACH_ALL,
            tags: RelationOp::DETACH_ALL,
        );

        $out = $this->s->serialize($req);

        $this->assertSame('empty', $out['groups']);
        $this->assertSame('empty', $out['tags']);
    }

    public function testListQueryToQueryParamsSkipsNulls(): void
    {
        $q = new ListContactsQuery(q: 'john', perPage: 50, page: 2);

        $params = $this->s->queryParams($q);

        $this->assertSame([
            'q' => 'john',
            'per_page' => 50,
            'page' => 2,
        ], $params);
    }

    public function testListQueryToQueryParamsWithStatus(): void
    {
        $q = new ListContactsQuery(status: ContactStatus::BLOCKED, perPage: 100, page: 1);

        $params = $this->s->queryParams($q);

        $this->assertSame(['status' => 2, 'per_page' => 100, 'page' => 1], $params);
    }

    public function testMissingDateFieldStaysNullable(): void
    {
        $payload = $this->contactPayload();
        $payload['date_of_birth'] = null;

        $c = $this->s->deserialize($payload, Contact::class);

        $this->assertNull($c->dateOfBirth);
    }

    public function testInvalidDateThrowsSerializationException(): void
    {
        $payload = $this->contactPayload();
        $payload['date_of_birth'] = 'not-a-date';

        $this->expectException(SerializationException::class);

        $this->s->deserialize($payload, Contact::class);
    }

    public function testInvalidEnumValueThrowsSerializationException(): void
    {
        $payload = $this->contactPayload();
        $payload['status'] = 99;

        $this->expectException(SerializationException::class);

        $this->s->deserialize($payload, Contact::class);
    }

    public function testEnumWrappedLaravelCastIsUnwrapped(): void
    {
        $payload = $this->contactPayload();
        $payload['gender'] = ['value' => 2, 'label' => 'female'];

        $c = $this->s->deserialize($payload, Contact::class);

        $this->assertSame(ContactGender::FEMALE, $c->gender);
    }

    public function testEnumWithWrongTypeThrowsSerializationException(): void
    {
        $payload = $this->contactPayload();
        $payload['gender'] = ['value' => '2', 'label' => 'female'];

        $this->expectException(SerializationException::class);

        $this->s->deserialize($payload, Contact::class);
    }

    public function testContactTagDeserializes(): void
    {
        $payload = $this->contactPayload();
        $payload['tags'] = [['id' => 5, 'name' => 'vip']];

        $c = $this->s->deserialize($payload, Contact::class);

        $this->assertNotNull($c->tags);
        $this->assertInstanceOf(ContactTag::class, $c->tags[0]);
        $this->assertSame('vip', $c->tags[0]->name);
    }

    public function testPaginationLinksDeserializes(): void
    {
        $payload = [
            'data' => [],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0, 'from' => null, 'to' => null],
            'links' => [
                'first' => 'https://x/?page=1',
                'last' => 'https://x/?page=1',
                'prev' => null,
                'next' => null,
            ],
        ];

        $list = $this->s->deserialize($payload, ContactList::class);

        $this->assertSame('https://x/?page=1', $list->links->first);
        $this->assertNull($list->links->prev);
        $this->assertInstanceOf(PaginationMeta::class, $list->meta);
        $this->assertInstanceOf(PaginationLinks::class, $list->links);
    }

    /**
     * @return array<string, mixed>
     */
    private function contactPayload(): array
    {
        return [
            'id' => 42,
            'email' => 'a@b.c',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 2,
            'status' => 1,
            'date_of_birth' => '1990-01-01',
            'country_id' => 7,
            'city_id' => 13,
            'groups' => [['id' => 1, 'name' => 'newsletter']],
            'tags' => null,
            'created_at' => '2026-01-02T03:04:05+00:00',
        ];
    }
}
