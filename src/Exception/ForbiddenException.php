<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/** Thrown on HTTP 403 responses. The token lacks the required ability. */
final class ForbiddenException extends ApiException
{
}
