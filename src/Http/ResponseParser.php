<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Http;

use Psr\Http\Message\ResponseInterface;
use Sendigram\Ems\Client\Exception\ApiException;
use Sendigram\Ems\Client\Exception\BadRequestException;
use Sendigram\Ems\Client\Exception\ForbiddenException;
use Sendigram\Ems\Client\Exception\NotFoundException;
use Sendigram\Ems\Client\Exception\RateLimitException;
use Sendigram\Ems\Client\Exception\SerializationException;
use Sendigram\Ems\Client\Exception\ServerException;
use Sendigram\Ems\Client\Exception\UnauthorizedException;
use Sendigram\Ems\Client\Exception\ValidationException;
use Sendigram\Ems\Client\Serializer\ObjectSerializer;

/**
 * Inspects PSR-7 responses, decides whether to deserialize a model or throw a
 * mapped {@see ApiException}, and unwraps Laravel's `{"data": ...}` wrapper
 * transparently when present.
 */
final class ResponseParser
{
    public function __construct(private readonly ObjectSerializer $serializer)
    {
    }

    /**
     * Parse the response body. On non-2xx, throw a typed exception. On 2xx,
     * deserialize the body into the requested DTO class (or return null when
     * $expect is null — useful for `DELETE` calls).
     *
     * @template T of object
     *
     * @param class-string<T>|null $expect
     *
     * @return ($expect is null ? null : T)
     */
    public function parse(
        ResponseInterface $response,
        ?string $expect,
        string $requestMethod,
        string $requestUri,
    ): ?object {
        $status = $response->getStatusCode();
        $rawBody = (string) $response->getBody();
        $decoded = $this->tryDecode($rawBody);

        if ($status >= 200 && $status < 300) {
            if (null === $expect) {
                return null;
            }

            if (null === $decoded) {
                throw new SerializationException("Cannot deserialize response body: not valid JSON (HTTP {$status} for {$requestMethod} {$requestUri})");
            }

            $payload = $this->unwrap($decoded);

            /** @var T $obj */
            $obj = $this->serializer->deserialize($payload, $expect);

            return $obj;
        }

        throw $this->mapStatusToException($status, $rawBody, $response->getHeaders(), $decoded, $requestMethod, $requestUri);
    }

    /**
     * Same as {@see parse()} but returns also the original 2xx status code,
     * so callers like upsert (200 vs 201) can distinguish create from update.
     *
     * @template T of object
     *
     * @param class-string<T> $expect
     *
     * @return array{0: T, 1: int}
     */
    public function parseWithStatus(
        ResponseInterface $response,
        string $expect,
        string $requestMethod,
        string $requestUri,
    ): array {
        $status = $response->getStatusCode();
        /** @var T $obj */
        $obj = $this->parse($response, $expect, $requestMethod, $requestUri);

        return [$obj, $status];
    }

    /**
     * Status -> exception class. Unknown 4xx fall back to the base ApiException.
     *
     * @param array<string, list<string>> $headers
     * @param array<string, mixed>|null $decoded
     */
    private function mapStatusToException(
        int $status,
        string $rawBody,
        array $headers,
        ?array $decoded,
        string $requestMethod,
        string $requestUri,
    ): ApiException {
        $message = is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
            ? $decoded['message']
            : null;

        $args = [
            'statusCode' => $status,
            'responseBody' => $rawBody,
            'responseHeaders' => $headers,
            'decodedBody' => $decoded,
            'requestMethod' => $requestMethod,
            'requestUri' => $requestUri,
            'message' => $message,
        ];

        return match (true) {
            400 === $status => new BadRequestException(...$args),
            401 === $status => new UnauthorizedException(...$args),
            403 === $status => new ForbiddenException(...$args),
            404 === $status => new NotFoundException(...$args),
            422 === $status => new ValidationException(...$args),
            429 === $status => new RateLimitException(...$args),
            $status >= 500 && $status < 600 => new ServerException(...$args),
            default => new ApiException(...$args),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryDecode(string $body): ?array
    {
        if ('' === $body) {
            return null;
        }
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Laravel API resources wrap single-item responses in `{"data": {...}}`.
     * If the decoded payload looks like such a wrapper (only the `data` key,
     * and it's an array), unwrap it.
     *
     * @param array<string, mixed> $decoded
     *
     * @return array<string, mixed>
     */
    private function unwrap(array $decoded): array
    {
        if (
            1 === count($decoded)
            && array_key_exists('data', $decoded)
            && is_array($decoded['data'])
            && !array_is_list($decoded['data'])
        ) {
            /** @var array<string, mixed> $inner */
            $inner = $decoded['data'];

            return $inner;
        }

        return $decoded;
    }
}
