<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

/** A contact group as returned by EMS (id + display name). */
final class ContactGroup
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
