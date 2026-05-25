<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Base class for every exception thrown by the SDK.
 *
 * Catch this if you want a single hook for any failure originating from the
 * EMS PHP SDK (configuration error, transport failure, API error, etc.).
 */
class EmsException extends \RuntimeException
{
}
