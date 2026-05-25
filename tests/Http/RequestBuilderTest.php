<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Http;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Configuration;
use Sendigram\Ems\Client\Http\RequestBuilder;

final class RequestBuilderTest extends TestCase
{
    private RequestBuilder $builder;

    protected function setUp(): void
    {
        $factory = new HttpFactory();
        $this->builder = new RequestBuilder(
            Configuration::default('tok-abc')->withUserAgent('test-agent/1.0'),
            $factory,
            $factory,
        );
    }

    public function testBuildsAbsoluteUriFromRelativePath(): void
    {
        $r = $this->builder->build('GET', 'contacts');

        $this->assertSame('GET', $r->getMethod());
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts', (string) $r->getUri());
    }

    public function testStripsLeadingSlashOnPath(): void
    {
        $r = $this->builder->build('GET', '/contacts/42');

        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts/42', (string) $r->getUri());
    }

    public function testAddsAuthorizationHeader(): void
    {
        $r = $this->builder->build('GET', 'contacts');

        $this->assertSame('Bearer tok-abc', $r->getHeaderLine('Authorization'));
    }

    public function testAddsUserAgent(): void
    {
        $r = $this->builder->build('GET', 'contacts');

        $this->assertSame('test-agent/1.0', $r->getHeaderLine('User-Agent'));
    }

    public function testAddsAcceptJson(): void
    {
        $r = $this->builder->build('GET', 'contacts');

        $this->assertSame('application/json', $r->getHeaderLine('Accept'));
    }

    public function testSerializesQueryParams(): void
    {
        $r = $this->builder->build('GET', 'contacts', query: ['q' => 'john', 'page' => 2, 'per_page' => 50]);

        $this->assertSame('q=john&page=2&per_page=50', $r->getUri()->getQuery());
    }

    public function testQueryParamsUrlEncoded(): void
    {
        $r = $this->builder->build('DELETE', 'contacts/by-email/foo+bar@example.com');

        $this->assertStringContainsString('foo%2Bbar%40example.com', (string) $r->getUri());
    }

    public function testSetsJsonBody(): void
    {
        $r = $this->builder->build('POST', 'contacts', body: ['email' => 'a@b.c']);

        $this->assertSame('application/json', $r->getHeaderLine('Content-Type'));
        $this->assertSame('{"email":"a@b.c"}', (string) $r->getBody());
    }

    public function testOmitsContentTypeWithoutBody(): void
    {
        $r = $this->builder->build('GET', 'contacts');

        $this->assertSame('', $r->getHeaderLine('Content-Type'));
        $this->assertSame('', (string) $r->getBody());
    }
}
