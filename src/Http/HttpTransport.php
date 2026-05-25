<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sendigram\Ems\Client\Exception\NetworkException;

/**
 * Thin wrapper around a PSR-18 client that catches transport failures
 * (`ClientExceptionInterface` — timeouts, DNS, TLS) and rethrows them as
 * {@see NetworkException} so callers can use a single SDK-rooted hierarchy.
 */
final class HttpTransport
{
    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException($e->getMessage(), 0, $e);
        }
    }
}
