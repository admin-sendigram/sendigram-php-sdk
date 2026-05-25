<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Requests;

use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;

/**
 * Payload for `PUT /contacts/{id}`. Every field is optional — only the
 * provided fields will be applied; omitted fields are left unchanged.
 *
 * Note: unlike {@see CreateContactRequest}, `mode` is nullable; passing `null`
 * means "do not include the field in the request, let the server pick its
 * default" (`sync`).
 *
 * `groups` / `tags` accept either an int list or {@see RelationOp::DETACH_ALL}
 * to detach all existing associations. `fields` is a `key => value` map of
 * custom field values.
 */
final class UpdateContactRequest
{
    /**
     * @param int[]|RelationOp|null $groups
     * @param int[]|RelationOp|null $tags
     * @param array<string, string>|null $fields
     */
    public function __construct(
        public ?string $email = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?ContactGender $gender = null,
        public ?\DateTimeImmutable $dateOfBirth = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?ContactStatus $status = null,
        public ?SyncMode $mode = null,
        public array|RelationOp|null $groups = null,
        public array|RelationOp|null $tags = null,
        public ?array $fields = null,
    ) {
    }
}
