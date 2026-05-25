<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Configuration;
use Sendigram\Ems\Client\EmsClient;
use Sendigram\Ems\Client\Resources\ContactsResource;
use Sendigram\Ems\Client\Tests\Support\MockHttpClient;

final class EmsClientTest extends TestCase
{
    public function testAcceptsTokenString(): void
    {
        $client = new EmsClient('tok-abc');

        $this->assertInstanceOf(ContactsResource::class, $client->contacts);
    }

    public function testAcceptsConfigurationObject(): void
    {
        $client = new EmsClient(Configuration::default('tok-abc')->withTimeout(60));

        $this->assertInstanceOf(ContactsResource::class, $client->contacts);
    }

    public function testInjectsCustomHttpClient(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $http->enqueue($factory->createResponse(200)
            ->withBody($factory->createStream(json_encode([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0, 'from' => null, 'to' => null],
                'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            ], JSON_THROW_ON_ERROR))));

        $client = new EmsClient(
            config: Configuration::default('tok-abc'),
            httpClient: $http,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $client->contacts->list();

        $this->assertCount(1, $http->requests());
        $this->assertSame('Bearer tok-abc', $http->requests()[0]->getHeaderLine('Authorization'));
    }

    public function testContactsIsLazyAndCached(): void
    {
        $client = new EmsClient('tok-abc');

        $this->assertSame($client->contacts, $client->contacts);
    }

    public function testUnknownPropertyThrows(): void
    {
        $client = new EmsClient('tok-abc');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown EMS resource: campaigns');

        // @phpstan-ignore-next-line — intentional bad property access
        $_ = $client->campaigns;
    }
}
