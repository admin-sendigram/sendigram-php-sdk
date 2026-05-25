<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Thrown on HTTP 429 responses. The token exceeded the per-minute rate limit
 * (EMS enforces `120/min` by default).
 *
 * Use {@see retryAfter()} to obtain the server-supplied delay, if present.
 */
final class RateLimitException extends ApiException
{
    /**
     * Number of seconds the client should wait before retrying, or null if
     * the server did not include a numeric `Retry-After` header.
     *
     * Only the delta-seconds form of `Retry-After` is recognised; HTTP-date
     * values are ignored (return null) since EMS uses the integer form.
     */
    public function retryAfter(): ?int
    {
        $values = $this->responseHeaders['Retry-After']
            ?? $this->responseHeaders['retry-after']
            ?? [];

        $value = $values[0] ?? null;

        if (null === $value) {
            return null;
        }

        return ctype_digit((string) $value) ? (int) $value : null;
    }
}
