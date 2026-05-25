<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

/**
 * Outcome of a `POST /contacts/upsert` call.
 *
 * `created` is `true` when EMS responded with HTTP 201 (contact created) and
 * `false` when EMS responded with HTTP 200 (contact updated).
 */
final class UpsertResult
{
    public function __construct(
        public readonly Contact $contact,
        public readonly bool $created,
    ) {
    }
}
