<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Thrown for pre-flight client configuration errors (missing token, malformed
 * base URL, invalid timeout, etc.). The HTTP transport never executed.
 */
final class ConfigurationException extends EmsException
{
}
