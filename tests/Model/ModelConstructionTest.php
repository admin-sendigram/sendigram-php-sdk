<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Model;

use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Model\Contact;
use Sendigram\Ems\Client\Model\ContactGroup;
use Sendigram\Ems\Client\Model\ContactList;
use Sendigram\Ems\Client\Model\ContactTag;
use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\PaginationLinks;
use Sendigram\Ems\Client\Model\PaginationMeta;
use Sendigram\Ems\Client\Model\UpsertResult;

final class ModelConstructionTest extends TestCase
{
    public function testContactConstruction(): void
    {
        $c = new Contact(
            id: 42,
            email: 'a@b.c',
            firstName: 'A',
            lastName: 'B',
            gender: ContactGender::FEMALE,
            status: ContactStatus::ACTIVE,
            dateOfBirth: new \DateTimeImmutable('1990-01-01'),
            countryId: 7,
            cityId: 13,
            groups: [new ContactGroup(1, 'newsletter')],
            tags: null,
            createdAt: new \DateTimeImmutable('2026-01-01T00:00:00Z'),
        );

        $this->assertSame(42, $c->id);
        $this->assertSame('a@b.c', $c->email);
        $this->assertSame(ContactGender::FEMALE, $c->gender);
        $this->assertNotNull($c->groups);
        $this->assertCount(1, $c->groups);
        $this->assertNull($c->tags);
    }

    public function testContactGroupAndTagConstruction(): void
    {
        $g = new ContactGroup(1, 'newsletter');
        $t = new ContactTag(2, 'vip');

        $this->assertSame(1, $g->id);
        $this->assertSame('newsletter', $g->name);
        $this->assertSame(2, $t->id);
        $this->assertSame('vip', $t->name);
    }

    public function testContactListHasNextPage(): void
    {
        $list = new ContactList(
            data: [],
            meta: new PaginationMeta(currentPage: 1, lastPage: 5, perPage: 20, total: 100, from: 1, to: 20),
            links: new PaginationLinks(first: null, last: null, prev: null, next: null),
        );

        $this->assertTrue($list->hasNextPage());
    }

    public function testContactListHasNoNextPageOnLastPage(): void
    {
        $list = new ContactList(
            data: [],
            meta: new PaginationMeta(currentPage: 5, lastPage: 5, perPage: 20, total: 100, from: 81, to: 100),
            links: new PaginationLinks(first: null, last: null, prev: null, next: null),
        );

        $this->assertFalse($list->hasNextPage());
    }

    public function testUpsertResultConstruction(): void
    {
        $contact = new Contact(
            id: 1, email: null, firstName: null, lastName: null,
            gender: null, status: null, dateOfBirth: null,
            countryId: null, cityId: null, groups: null, tags: null,
            createdAt: new \DateTimeImmutable('2026-01-01T00:00:00Z'),
        );

        $r = new UpsertResult($contact, true);

        $this->assertSame($contact, $r->contact);
        $this->assertTrue($r->created);
    }
}
