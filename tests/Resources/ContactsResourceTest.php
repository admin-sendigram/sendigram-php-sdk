<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Resources;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Configuration;
use Sendigram\Ems\Client\Exception\ValidationException;
use Sendigram\Ems\Client\Http\HttpTransport;
use Sendigram\Ems\Client\Http\RequestBuilder;
use Sendigram\Ems\Client\Http\ResponseParser;
use Sendigram\Ems\Client\Model\Contact;
use Sendigram\Ems\Client\Model\ContactList;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;
use Sendigram\Ems\Client\Model\Requests\CreateContactRequest;
use Sendigram\Ems\Client\Model\Requests\ListContactsQuery;
use Sendigram\Ems\Client\Model\Requests\UpdateContactRequest;
use Sendigram\Ems\Client\Model\Requests\UpsertContactRequest;
use Sendigram\Ems\Client\Model\UpsertResult;
use Sendigram\Ems\Client\Resources\ContactsResource;
use Sendigram\Ems\Client\Serializer\ObjectSerializer;
use Sendigram\Ems\Client\Tests\Support\MockHttpClient;

final class ContactsResourceTest extends TestCase
{
    private MockHttpClient $http;
    private ContactsResource $resource;
    private HttpFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new HttpFactory();
        $this->http = new MockHttpClient();
        $config = Configuration::default('tok-abc');
        $serializer = new ObjectSerializer();

        $this->resource = new ContactsResource(
            new HttpTransport($this->http),
            new RequestBuilder($config, $this->factory, $this->factory),
            new ResponseParser($serializer),
            $serializer,
        );
    }

    public function testGetSendsCorrectRequest(): void
    {
        $this->enqueueFixture('contacts/single.json');

        $contact = $this->resource->get(42);

        $sent = $this->http->requests()[0];
        $this->assertSame('GET', $sent->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts/42', (string) $sent->getUri());
        $this->assertSame('Bearer tok-abc', $sent->getHeaderLine('Authorization'));

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame(42, $contact->id);
        $this->assertSame('john@example.com', $contact->email);
    }

    public function testListWithoutFiltersUsesDefaults(): void
    {
        $this->enqueueFixture('contacts/list-page-1.json');

        $list = $this->resource->list();

        $this->assertInstanceOf(ContactList::class, $list);
        $this->assertCount(2, $list->data);
        $this->assertSame(2, $list->meta->lastPage);

        $sent = $this->http->requests()[0];
        $this->assertSame('GET', $sent->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts?per_page=20&page=1', (string) $sent->getUri());
    }

    public function testListWithFiltersSerializesQuery(): void
    {
        $this->enqueueFixture('contacts/list-page-1.json');

        $this->resource->list(new ListContactsQuery(
            q: 'john',
            status: ContactStatus::ACTIVE,
            perPage: 50,
            page: 2,
        ));

        $sent = $this->http->requests()[0];
        $this->assertSame(
            'q=john&status=1&per_page=50&page=2',
            $sent->getUri()->getQuery(),
        );
    }

    public function testListNamedArgumentsShortcut(): void
    {
        $this->enqueueFixture('contacts/list-page-1.json');

        $this->resource->list(q: 'alice', perPage: 10);

        $this->assertStringContainsString('q=alice', $this->http->requests()[0]->getUri()->getQuery());
        $this->assertStringContainsString('per_page=10', $this->http->requests()[0]->getUri()->getQuery());
    }

    public function testCreateWithDto(): void
    {
        $this->enqueueFixture('contacts/single.json', 201);

        $contact = $this->resource->create(new CreateContactRequest(
            email: 'john@example.com',
            firstName: 'John',
            lastName: 'Doe',
        ));

        $this->assertSame(42, $contact->id);

        $sent = $this->http->requests()[0];
        $this->assertSame('POST', $sent->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts', (string) $sent->getUri());
        $this->assertSame('application/json', $sent->getHeaderLine('Content-Type'));
        $this->assertSame(
            json_decode('{"email":"john@example.com","first_name":"John","last_name":"Doe","mode":"sync"}', true),
            json_decode((string) $sent->getBody(), true),
        );
    }

    public function testCreateNamedArgumentsShortcut(): void
    {
        $this->enqueueFixture('contacts/single.json', 201);

        $this->resource->create(
            email: 'john@example.com',
            firstName: 'John',
            lastName: 'Doe',
            mode: SyncMode::ATTACH,
            groups: [1, 2],
        );

        $body = json_decode((string) $this->http->requests()[0]->getBody(), true);
        $this->assertSame('john@example.com', $body['email']);
        $this->assertSame('attach', $body['mode']);
        $this->assertSame([1, 2], $body['groups']);
    }

    public function testCreateRequiresEmailWithoutDto(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->resource->create(firstName: 'John');
    }

    public function testCreateSendsTagsAndFields(): void
    {
        $this->enqueueFixture('contacts/single.json', 201);

        $this->resource->create(
            email: 'john@example.com',
            tags: [10, 11],
            fields: ['plan' => 'pro'],
        );

        $body = json_decode((string) $this->http->requests()[0]->getBody(), true);
        $this->assertSame([10, 11], $body['tags']);
        $this->assertSame(['plan' => 'pro'], $body['fields']);
    }

    public function testUpdateDetachAllSerializesEmptyLiteral(): void
    {
        $this->enqueueFixture('contacts/single.json');

        $this->resource->update(
            42,
            groups: RelationOp::DETACH_ALL,
            tags: RelationOp::DETACH_ALL,
        );

        $body = json_decode((string) $this->http->requests()[0]->getBody(), true);
        $this->assertSame('empty', $body['groups']);
        $this->assertSame('empty', $body['tags']);
    }

    public function testUpdateWithDto(): void
    {
        $this->enqueueFixture('contacts/single.json');

        $this->resource->update(42, new UpdateContactRequest(firstName: 'Jane'));

        $sent = $this->http->requests()[0];
        $this->assertSame('PUT', $sent->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts/42', (string) $sent->getUri());
        $this->assertSame(['first_name' => 'Jane'], json_decode((string) $sent->getBody(), true));
    }

    public function testUpdateNamedShortcut(): void
    {
        $this->enqueueFixture('contacts/single.json');

        $this->resource->update(42, firstName: 'Jane', lastName: 'Smith');

        $body = json_decode((string) $this->http->requests()[0]->getBody(), true);
        $this->assertSame(['first_name' => 'Jane', 'last_name' => 'Smith'], $body);
    }

    public function testUpsertCreatedReturnsCreatedTrue(): void
    {
        $this->enqueueFixture('contacts/single.json', 201);

        $result = $this->resource->upsert(new UpsertContactRequest(email: 'john@example.com'));

        $this->assertInstanceOf(UpsertResult::class, $result);
        $this->assertTrue($result->created);
        $this->assertSame(42, $result->contact->id);

        $sent = $this->http->requests()[0];
        $this->assertSame('POST', $sent->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts/upsert', (string) $sent->getUri());
    }

    public function testUpsertUpdatedReturnsCreatedFalse(): void
    {
        $this->enqueueFixture('contacts/single.json', 200);

        $result = $this->resource->upsert(email: 'john@example.com');

        $this->assertFalse($result->created);
    }

    public function testDeleteSendsCorrectRequest(): void
    {
        $this->http->enqueue($this->factory->createResponse(200)
            ->withBody($this->factory->createStream(json_encode(['message' => 'ok'], JSON_THROW_ON_ERROR))));

        $this->resource->delete(42);

        $sent = $this->http->requests()[0];
        $this->assertSame('DELETE', $sent->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts/42', (string) $sent->getUri());
    }

    public function testDeleteByEmailUrlEncodesEmail(): void
    {
        $this->http->enqueue($this->factory->createResponse(200)
            ->withBody($this->factory->createStream(json_encode(['message' => 'ok'], JSON_THROW_ON_ERROR))));

        $this->resource->deleteByEmail('foo+bar@example.com');

        $sent = $this->http->requests()[0];
        $this->assertSame('DELETE', $sent->getMethod());
        $this->assertSame(
            'https://ems.sendigram.com/open-api/v1/contacts/by-email/foo%2Bbar%40example.com',
            (string) $sent->getUri(),
        );
    }

    public function testValidationErrorBubbles(): void
    {
        $body = json_encode([
            'message' => 'The given data was invalid.',
            'errors' => ['email' => ['The email must be a valid email address.']],
        ]);

        $this->http->enqueue($this->factory->createResponse(422)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream((string) $body)));

        try {
            $this->resource->create(email: 'not-an-email');
            $this->fail('expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['email' => ['The email must be a valid email address.']],
                $e->errors(),
            );
        }
    }

    public function testIterateFollowsPages(): void
    {
        $this->enqueueFixture('contacts/list-page-1.json');
        // Build page 2 inline (1 item, last page).
        $page2 = [
            'data' => [[
                'id' => 3, 'email' => 'carol@example.com',
                'first_name' => 'Carol', 'last_name' => null, 'gender' => null,
                'status' => 1, 'date_of_birth' => null,
                'country_id' => null, 'city_id' => null,
                'created_at' => '2026-01-01T00:00:00+00:00',
            ]],
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            'meta' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 2, 'total' => 3, 'from' => 3, 'to' => 3],
        ];
        $this->http->enqueue($this->factory->createResponse(200)
            ->withBody($this->factory->createStream(json_encode($page2, JSON_THROW_ON_ERROR))));

        $ids = [];
        foreach ($this->resource->iterate(perPage: 2) as $contact) {
            $ids[] = $contact->id;
        }

        $this->assertSame([1, 2, 3], $ids);
        $this->assertCount(2, $this->http->requests());
    }

    public function testIterateStopsAfterSinglePage(): void
    {
        $singlePage = [
            'data' => [[
                'id' => 1, 'email' => 'solo@example.com',
                'first_name' => null, 'last_name' => null, 'gender' => null,
                'status' => 1, 'date_of_birth' => null,
                'country_id' => null, 'city_id' => null,
                'created_at' => '2026-01-01T00:00:00+00:00',
            ]],
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 100, 'total' => 1, 'from' => 1, 'to' => 1],
        ];
        $this->http->enqueue($this->factory->createResponse(200)
            ->withBody($this->factory->createStream(json_encode($singlePage, JSON_THROW_ON_ERROR))));

        $ids = [];
        foreach ($this->resource->iterate() as $contact) {
            $ids[] = $contact->id;
        }

        $this->assertSame([1], $ids);
        $this->assertCount(1, $this->http->requests());
    }

    public function testDeleteByEmailRejectsForwardSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('forward slash');

        $this->resource->deleteByEmail('foo/bar@example.com');
    }

    private function enqueueFixture(string $relativePath, int $status = 200): void
    {
        $body = file_get_contents(__DIR__.'/../Fixtures/responses/'.$relativePath);
        $this->http->enqueue(
            $this->factory->createResponse($status)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->factory->createStream((string) $body)),
        );
    }
}
