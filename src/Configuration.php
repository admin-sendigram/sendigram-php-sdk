<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client;

use Sendigram\Ems\Client\Exception\ConfigurationException;

/**
 * Immutable configuration value object for {@see EmsClient}.
 *
 * Construct with {@see Configuration::default()} and chain `with*()` methods
 * to override individual fields. Every `with*()` returns a new instance, so
 * sharing a Configuration across multiple clients is safe.
 */
final class Configuration
{
    /** Current SDK version, used in the default User-Agent. */
    public const SDK_VERSION = '1.0.0';

    /** Default EMS production base URL (placeholder until confirmed). */
    public const DEFAULT_BASE_URL = 'https://ems.sendigram.com/open-api/v1/';

    /**
     * @param string $token bearer token issued by EMS
     * @param string $baseUrl absolute URL ending with a trailing slash
     * @param int $timeout HTTP request timeout, in whole seconds (> 0)
     * @param string $userAgent value sent in the `User-Agent` header
     */
    private function __construct(
        public readonly string $token,
        public readonly string $baseUrl,
        public readonly int $timeout,
        public readonly string $userAgent,
    ) {
    }

    /** Construct a Configuration with default base URL, timeout and User-Agent. */
    public static function default(string $token): self
    {
        $trimmed = trim($token);
        if ('' === $trimmed) {
            throw new ConfigurationException('token must be a non-empty string');
        }

        return new self(
            token: $trimmed,
            baseUrl: self::DEFAULT_BASE_URL,
            timeout: 30,
            userAgent: sprintf('ems-php/%s (PHP %s)', self::SDK_VERSION, PHP_VERSION),
        );
    }

    /** Replace the base URL. A trailing slash is appended if missing. */
    public function withBaseUrl(string $baseUrl): self
    {
        $baseUrl = trim($baseUrl);
        if ('' === $baseUrl) {
            throw new ConfigurationException('baseUrl must be a non-empty string');
        }
        if (!preg_match('#^https?://#i', $baseUrl)) {
            throw new ConfigurationException('baseUrl must be an absolute http(s) URL');
        }
        if (!str_ends_with($baseUrl, '/')) {
            $baseUrl .= '/';
        }

        return new self($this->token, $baseUrl, $this->timeout, $this->userAgent);
    }

    /** Replace the request timeout. Must be a positive integer (seconds). */
    public function withTimeout(int $timeout): self
    {
        if ($timeout <= 0) {
            throw new ConfigurationException('timeout must be a positive integer');
        }

        return new self($this->token, $this->baseUrl, $timeout, $this->userAgent);
    }

    /** Replace the User-Agent header value. Must be non-empty. */
    public function withUserAgent(string $userAgent): self
    {
        $userAgent = trim($userAgent);
        if ('' === $userAgent) {
            throw new ConfigurationException('userAgent must be a non-empty string');
        }

        return new self($this->token, $this->baseUrl, $this->timeout, $userAgent);
    }
}
