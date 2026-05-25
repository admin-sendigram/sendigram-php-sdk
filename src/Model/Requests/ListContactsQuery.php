<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Requests;

use Sendigram\Ems\Client\Model\Enum\ContactStatus;

/**
 * Query parameters for `GET /contacts`.
 *
 * `perPage` is capped at 100 by EMS regardless of what you send.
 */
final class ListContactsQuery
{
    public function __construct(
        public ?string $q = null,
        public ?ContactStatus $status = null,
        public int $perPage = 20,
        public int $page = 1,
    ) {
    }
}
