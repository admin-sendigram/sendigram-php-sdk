<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Enum;

/**
 * Sentinel for bulk relation operations on `groups` / `tags` request fields.
 *
 * EMS accepts either an array of IDs or the literal string `"empty"` to detach
 * all existing associations. The SDK exposes the literal as a typed enum case
 * so callers don't have to remember the magic string, and the ObjectSerializer
 * scalarizes BackedEnum to its `->value` automatically.
 */
enum RelationOp: string
{
    /** Detach all currently associated groups/tags from the contact. */
    case DETACH_ALL = 'empty';
}
