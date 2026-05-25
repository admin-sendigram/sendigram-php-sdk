<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Enum;

/** Contact gender flag. `UNSPECIFIED` is the default for missing data. */
enum ContactGender: int
{
    case UNSPECIFIED = 0;
    case MALE = 1;
    case FEMALE = 2;
}
