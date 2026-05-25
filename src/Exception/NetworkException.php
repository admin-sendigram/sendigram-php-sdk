<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Thrown when the underlying PSR-18 HTTP client failed to deliver the request
 * (DNS, TLS, timeout, broken connection). No HTTP response was received.
 */
final class NetworkException extends EmsException
{
}
