<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/** Thrown on HTTP 401 responses. The Bearer token is missing or invalid. */
final class UnauthorizedException extends ApiException
{
}
