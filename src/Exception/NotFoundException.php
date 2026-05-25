<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/** Thrown on HTTP 404 responses. The target resource does not exist. */
final class NotFoundException extends ApiException
{
}
