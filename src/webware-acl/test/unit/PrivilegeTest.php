<?php

declare(strict_types=1);

namespace Webware\AclTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Privilege;

#[CoversClass(Privilege::class)]
final class PrivilegeTest extends TestCase
{
    #[Test]
    public function readConstantValue(): void
    {
        self::assertSame('read', Privilege::READ);
    }

    #[Test]
    public function createConstantValue(): void
    {
        self::assertSame('create', Privilege::CREATE);
    }

    #[Test]
    public function updateConstantValue(): void
    {
        self::assertSame('update', Privilege::UPDATE);
    }

    #[Test]
    public function deleteConstantValue(): void
    {
        self::assertSame('delete', Privilege::DELETE);
    }
}
