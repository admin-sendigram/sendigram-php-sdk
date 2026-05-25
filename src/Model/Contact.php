<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;

/**
 * Contact as returned by the EMS Contacts API.
 *
 * Relations (`groups`, `tags`) are `null` when the API response did not
 * include them (Laravel `whenLoaded` behaviour); an empty array means
 * "included but empty".
 */
final class Contact
{
    /**
     * @param ContactGroup[]|null $groups
     * @param ContactTag[]|null $tags
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?ContactGender $gender,
        public readonly ?ContactStatus $status,
        public readonly ?\DateTimeImmutable $dateOfBirth,
        public readonly ?int $countryId,
        public readonly ?int $cityId,
        public readonly ?array $groups,
        public readonly ?array $tags,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
