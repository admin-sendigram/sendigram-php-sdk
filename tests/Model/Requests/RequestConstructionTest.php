<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Model\Requests;

use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;
use Sendigram\Ems\Client\Model\Requests\CreateContactRequest;
use Sendigram\Ems\Client\Model\Requests\ListContactsQuery;
use Sendigram\Ems\Client\Model\Requests\UpdateContactRequest;
use Sendigram\Ems\Client\Model\Requests\UpsertContactRequest;

final class RequestConstructionTest extends TestCase
{
    public function testCreateRequiresEmailDefaultsRest(): void
    {
        $r = new CreateContactRequest(email: 'a@b.c');

        $this->assertSame('a@b.c', $r->email);
        $this->assertNull($r->firstName);
        $this->assertNull($r->groups);
        $this->assertSame(SyncMode::SYNC, $r->mode);
    }

    public function testCreateAcceptsAllFields(): void
    {
        $r = new CreateContactRequest(
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
            tags: [9],
            fields: ['birthday_gift' => 'mug', 'plan' => 'pro'],
        );

        $this->assertSame('A', $r->firstName);
        $this->assertSame('PL', $r->country);
        $this->assertSame(SyncMode::ATTACH, $r->mode);
        $this->assertSame([1, 2], $r->groups);
        $this->assertSame([9], $r->tags);
        $this->assertSame(['birthday_gift' => 'mug', 'plan' => 'pro'], $r->fields);
    }

    public function testCreateAcceptsDetachAllSentinelForRelations(): void
    {
        $r = new CreateContactRequest(
            email: 'a@b.c',
            groups: RelationOp::DETACH_ALL,
            tags: RelationOp::DETACH_ALL,
        );

        $this->assertSame(RelationOp::DETACH_ALL, $r->groups);
        $this->assertSame(RelationOp::DETACH_ALL, $r->tags);
    }

    public function testUpdateAcceptsTagsFieldsAndDetachAll(): void
    {
        $r = new UpdateContactRequest(
            groups: RelationOp::DETACH_ALL,
            tags: [3, 4],
            fields: ['plan' => 'free'],
        );

        $this->assertSame(RelationOp::DETACH_ALL, $r->groups);
        $this->assertSame([3, 4], $r->tags);
        $this->assertSame(['plan' => 'free'], $r->fields);
    }

    public function testUpsertCarriesTagsAndFields(): void
    {
        $r = new UpsertContactRequest(
            email: 'a@b.c',
            tags: [7],
            fields: ['plan' => 'pro'],
        );

        $this->assertSame([7], $r->tags);
        $this->assertSame(['plan' => 'pro'], $r->fields);
    }

    public function testUpdateAllFieldsNullable(): void
    {
        $r = new UpdateContactRequest();

        $this->assertNull($r->email);
        $this->assertNull($r->firstName);
        $this->assertNull($r->mode);
    }

    public function testUpsertSharesShapeWithCreate(): void
    {
        $r = new UpsertContactRequest(email: 'a@b.c', firstName: 'A');

        $this->assertSame('a@b.c', $r->email);
        $this->assertSame('A', $r->firstName);
        $this->assertSame(SyncMode::SYNC, $r->mode);
    }

    public function testListDefaults(): void
    {
        $q = new ListContactsQuery();

        $this->assertNull($q->q);
        $this->assertNull($q->status);
        $this->assertSame(20, $q->perPage);
        $this->assertSame(1, $q->page);
    }

    public function testListWithFilters(): void
    {
        $q = new ListContactsQuery(q: 'john', status: ContactStatus::ACTIVE, perPage: 50, page: 2);

        $this->assertSame('john', $q->q);
        $this->assertSame(ContactStatus::ACTIVE, $q->status);
        $this->assertSame(50, $q->perPage);
        $this->assertSame(2, $q->page);
    }
}
