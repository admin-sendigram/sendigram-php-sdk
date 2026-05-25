<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Base for any non-2xx HTTP response received from the EMS API.
 *
 * Carries the full response context so callers can introspect status code,
 * raw body, decoded body and the originating request.
 */
class ApiException extends EmsException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $responseBody,
        /** @var array<string, list<string>> */
        public readonly array $responseHeaders,
        /** @var array<string, mixed>|null */
        public readonly ?array $decodedBody,
        public readonly string $requestMethod,
        public readonly string $requestUri,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "EMS API returned HTTP {$statusCode}");
    }
}
