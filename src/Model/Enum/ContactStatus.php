<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Enum;

/** Contact status flag returned by EMS. */
enum ContactStatus: int
{
    case ACTIVE = 1;
    case BLOCKED = 2;
}
