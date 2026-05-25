<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Model\Enum;

use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Model\Enum\ContactGender;
use Sendigram\Ems\Client\Model\Enum\ContactStatus;
use Sendigram\Ems\Client\Model\Enum\SyncMode;

final class EnumTest extends TestCase
{
    public function testContactStatusValues(): void
    {
        $this->assertSame(1, ContactStatus::ACTIVE->value);
        $this->assertSame(2, ContactStatus::BLOCKED->value);
        $this->assertSame(ContactStatus::ACTIVE, ContactStatus::from(1));
        $this->assertSame(ContactStatus::BLOCKED, ContactStatus::from(2));
    }

    public function testContactGenderValues(): void
    {
        $this->assertSame(0, ContactGender::UNSPECIFIED->value);
        $this->assertSame(1, ContactGender::MALE->value);
        $this->assertSame(2, ContactGender::FEMALE->value);
    }

    public function testSyncModeValues(): void
    {
        $this->assertSame('sync', SyncMode::SYNC->value);
        $this->assertSame('attach', SyncMode::ATTACH->value);
    }
}
