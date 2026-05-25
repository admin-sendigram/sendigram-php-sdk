<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model\Requests;

use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\RelationOp;
use Sendigram\Ems\Client\Model\Enum\SyncMode;

/**
 * Payload for `POST /contacts/upsert`. Same field set as
 * {@see CreateContactRequest}; declared as its own class so future drift
 * doesn't break callers that type-hint on `Upsert*` specifically.
 */
final class UpsertContactRequest
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
