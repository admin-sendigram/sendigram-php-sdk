<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Support;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

final class MockHttpClientTest extends TestCase
{
    public function testReturnsScriptedResponsesInOrder(): void
    {
        $factory = new HttpFactory();
        $mock = new MockHttpClient();
        $mock->enqueue($factory->createResponse(200)->withBody($factory->createStream('A')));
        $mock->enqueue($factory->createResponse(201)->withBody($factory->createStream('B')));

        $r1 = $mock->sendRequest($factory->createRequest('GET', '/a'));
        $r2 = $mock->sendRequest($factory->createRequest('POST', '/b'));

        $this->assertSame(200, $r1->getStatusCode());
        $this->assertSame('A', (string) $r1->getBody());
        $this->assertSame(201, $r2->getStatusCode());
        $this->assertSame('B', (string) $r2->getBody());
    }

    public function testRecordsRequests(): void
    {
        $factory = new HttpFactory();
        $mock = new MockHttpClient();
        $mock->enqueue($factory->createResponse(200));

        $mock->sendRequest($factory->createRequest('GET', 'https://example.com/x'));

        $this->assertCount(1, $mock->requests());
        $this->assertSame('GET', $mock->requests()[0]->getMethod());
        $this->assertSame('https://example.com/x', (string) $mock->requests()[0]->getUri());
    }

    public function testThrowsScriptedException(): void
    {
        $factory = new HttpFactory();
        $mock = new MockHttpClient();
        $mock->enqueueException(new \GuzzleHttp\Exception\ConnectException(
            'connection refused',
            $factory->createRequest('GET', '/x'),
        ));

        $this->expectException(\Psr\Http\Client\ClientExceptionInterface::class);

        $mock->sendRequest($factory->createRequest('GET', '/x'));
    }

    public function testThrowsWhenQueueEmpty(): void
    {
        $factory = new HttpFactory();
        $mock = new MockHttpClient();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MockHttpClient queue is empty');

        $mock->sendRequest($factory->createRequest('GET', '/x'));
    }
}
