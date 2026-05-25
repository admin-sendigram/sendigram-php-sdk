<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Sendigram\Ems\Client\Configuration;
use Sendigram\Ems\Client\Exception\SerializationException;

/**
 * Builds PSR-7 requests for the EMS API.
 *
 * Adds:
 *   - Authorization: Bearer {token}
 *   - User-Agent (from Configuration)
 *   - Accept: application/json
 *   - Content-Type: application/json (only when body is non-null)
 *
 * Composes absolute URIs from `Configuration::$baseUrl` + the supplied path,
 * stripping any leading slash on the path so the trailing slash of baseUrl is
 * respected.
 */
final class RequestBuilder
{
    public function __construct(
        private readonly Configuration $config,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param array<string, scalar> $query
     * @param array<string, mixed>|null $body
     */
    public function build(
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
    ): RequestInterface {
        $uri = $this->config->baseUrl.$this->encodePath(ltrim($path, '/'));

        if ([] !== $query) {
            $uri .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Authorization', 'Bearer '.$this->config->token)
            ->withHeader('User-Agent', $this->config->userAgent)
            ->withHeader('Accept', 'application/json');

        if (null !== $body) {
            try {
                $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException $e) {
                throw new SerializationException('Failed to JSON-encode request body', 0, $e);
            }

            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($json));
        }

        return $request;
    }

    /**
     * Encode each path segment individually so characters like `+`, `@`, `/`
     * inside path parameters (e.g. emails in `/contacts/by-email/{email}`)
     * are percent-encoded but path separators between segments remain intact.
     */
    private function encodePath(string $path): string
    {
        $segments = explode('/', $path);
        $encoded = array_map(fn (string $s): string => rawurlencode($s), $segments);

        return implode('/', $encoded);
    }
}
