<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Enum;

/**
 * Controls how a request applies the `groups` and `tags` lists to a contact.
 * Both relations share a single `mode` field on the request, so the same value
 * applies to whichever (or both) lists are provided.
 *
 * - SYNC   — replace all existing associations with the provided IDs.
 * - ATTACH — add the provided IDs without removing existing ones.
 *
 * To detach everything regardless of mode, pass
 * {@see RelationOp::DETACH_ALL} as the relation value.
 */
enum SyncMode: string
{
    case SYNC = 'sync';
    case ATTACH = 'attach';
}
