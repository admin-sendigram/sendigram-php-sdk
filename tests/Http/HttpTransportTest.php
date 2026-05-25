<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Exception\NetworkException;
use Sendigram\Ems\Client\Http\HttpTransport;
use Sendigram\Ems\Client\Tests\Support\MockHttpClient;

final class HttpTransportTest extends TestCase
{
    public function testForwardsResponse(): void
    {
        $factory = new HttpFactory();
        $mock = new MockHttpClient();
        $mock->enqueue($factory->createResponse(204));

        $transport = new HttpTransport($mock);
        $response = $transport->send($factory->createRequest('DELETE', '/contacts/1'));

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testWrapsClientExceptionAsNetworkException(): void
    {
        $factory = new HttpFactory();
        $mock = new MockHttpClient();
        $mock->enqueueException(new ConnectException(
            'connection refused',
            $factory->createRequest('GET', '/contacts'),
        ));

        $transport = new HttpTransport($mock);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('connection refused');

        $transport->send($factory->createRequest('GET', '/contacts'));
    }
}
