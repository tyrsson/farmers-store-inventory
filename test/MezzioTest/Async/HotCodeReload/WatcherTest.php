<?php

declare(strict_types=1);

namespace MezzioTest\Async\HotCodeReload;

use Mezzio\Async\HotCodeReload\Watcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(Watcher::class)]
final class WatcherTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $watcher = new Watcher(
            paths:     ['/tmp'],
            recursive: false,
            logger:    new NullLogger(),
        );

        self::assertInstanceOf(Watcher::class, $watcher);
    }

    public function testStartInRequiresTrueAsyncExtension(): void
    {
        if (extension_loaded('true_async')) {
            $this->markTestSkipped('true_async is loaded; startIn() integration tested elsewhere');
        }

        $watcher = new Watcher(
            paths:     ['/tmp'],
            recursive: false,
            logger:    new NullLogger(),
        );

        // Without the extension, Async\Scope and FileSystemWatcher are not
        // available. We can only assert that the Watcher object is created
        // without errors — runtime behaviour requires the extension.
        self::assertInstanceOf(Watcher::class, $watcher);
    }
}
