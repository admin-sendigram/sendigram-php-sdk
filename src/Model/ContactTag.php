<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

/** A contact tag as returned by EMS (id + display name). */
final class ContactTag
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
