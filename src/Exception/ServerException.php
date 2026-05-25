<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/** Thrown on HTTP 5xx responses. The server failed. Retry is usually safe. */
final class ServerException extends ApiException
{
}
