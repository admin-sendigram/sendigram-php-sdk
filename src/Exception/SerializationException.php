<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Thrown when the SDK cannot serialize an outgoing request or deserialize an
 * incoming response (malformed JSON, missing required fields, unknown shape).
 */
final class SerializationException extends EmsException
{
}
