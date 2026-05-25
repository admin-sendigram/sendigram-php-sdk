<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Small in-process PSR-18 client used in the SDK tests.
 *
 * Tests `enqueue()` responses (or exceptions) in the order they expect them
 * to be consumed; `sendRequest()` pops one off the queue per call. Each sent
 * request is recorded and inspectable via `requests()`.
 */
final class MockHttpClient implements ClientInterface
{
    /** @var array<int, ResponseInterface|ClientExceptionInterface> */
    private array $queue = [];

    /** @var array<int, RequestInterface> */
    private array $sent = [];

    public function enqueue(ResponseInterface $response): void
    {
        $this->queue[] = $response;
    }

    public function enqueueException(ClientExceptionInterface $exception): void
    {
        $this->queue[] = $exception;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sent[] = $request;

        if ([] === $this->queue) {
            throw new \RuntimeException('MockHttpClient queue is empty');
        }

        $next = array_shift($this->queue);

        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }

    /** @return list<RequestInterface> */
    public function requests(): array
    {
        return $this->sent;
    }
}
