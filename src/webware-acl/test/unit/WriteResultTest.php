<?php

declare(strict_types=1);

namespace Webware\AclTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\WriteResult;

#[CoversClass(WriteResult::class)]
final class WriteResultTest extends TestCase
{
    #[Test]
    public function successCaseValue(): void
    {
        self::assertSame('webware_acl.write_result.success', WriteResult::Success->value);
    }

    #[Test]
    public function failureCaseValue(): void
    {
        self::assertSame('webware_acl.write_result.failure', WriteResult::Failure->value);
    }

    #[Test]
    public function casesAreDistinct(): void
    {
        self::assertNotSame(WriteResult::Success->value, WriteResult::Failure->value);
    }
}
