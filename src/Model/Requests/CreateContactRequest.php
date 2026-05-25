<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Requests;

use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;

/**
 * Payload for `POST /contacts`. `email` is the only required field.
 *
 * `country` is the ISO 3166-1 alpha-2 code (e.g. `PL`, `US`). `city` is the
 * English city name and may only be provided when `country` is also set.
 *
 * `groups` and `tags` are lists of IDs. Pass {@see RelationOp::DETACH_ALL} to
 * detach every existing association instead of replacing/adding. The applied
 * semantics for arrays depend on {@see $mode}: `SYNC` (default) replaces all
 * existing associations, `ATTACH` adds without removing.
 *
 * `fields` is a map of custom-field `key => value` strings. Unknown keys are
 * silently ignored by the server; an empty string clears a known key.
 */
final class CreateContactRequest
{
    /**
     * @param int[]|RelationOp|null $groups
     * @param int[]|RelationOp|null $tags
     * @param array<string, string>|null $fields
     */
    public function __construct(
        public string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?ContactGender $gender = null,
        public ?\DateTimeImmutable $dateOfBirth = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?ContactStatus $status = null,
        public SyncMode $mode = SyncMode::SYNC,
        public array|RelationOp|null $groups = null,
        public array|RelationOp|null $tags = null,
        public ?array $fields = null,
    ) {
    }
}
