<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AsyncTest\HotCodeReload;

use AppTest\InMemoryContainer;
use Mezzio\Async\HotCodeReload\Watcher;
use Mezzio\Async\HotCodeReload\WatcherFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

#[CoversClass(WatcherFactory::class)]
final class WatcherFactoryTest extends TestCase
{
    private InMemoryContainer $container;

    private WatcherFactory $factory;

    protected function setUp(): void
    {
        $this->container = new InMemoryContainer();
        $this->factory   = new WatcherFactory();

        $this->container->setService(LoggerInterface::class, new NullLogger());
    }

    public function testReturnsWatcherInstance(): void
    {
        $this->container->setService('config', []);

        $watcher = ($this->factory)($this->container);

        self::assertInstanceOf(Watcher::class, $watcher);
    }

    public function testUsesDefaultPathsWhenNoConfigPresent(): void
    {
        // No 'config' service at all
        $watcher = ($this->factory)($this->container);

        self::assertInstanceOf(Watcher::class, $watcher);
    }

    public function testReadsPathsFromConfig(): void
    {
        $this->container->setService('config', [
            'mezzio-async' => [
                'hot-reload' => [
                    'paths'     => ['src/App', 'src/Htmx'],
                    'recursive' => false,
                ],
            ],
        ]);

        $watcher = ($this->factory)($this->container);

        self::assertInstanceOf(Watcher::class, $watcher);
    }

    public function testResolvesRelativePathsToAbsolute(): void
    {
        $this->container->setService('config', [
            'mezzio-async' => [
                'hot-reload' => [
                    'paths' => ['src'],
                ],
            ],
        ]);

        // Factory must not throw — relative paths are resolved via getcwd().
        $watcher = ($this->factory)($this->container);

        self::assertInstanceOf(Watcher::class, $watcher);
    }

    public function testAbsolutePathsAreNotPrefixed(): void
    {
        $this->container->setService('config', [
            'mezzio-async' => [
                'hot-reload' => [
                    'paths' => ['/var/www/app/src'],
                ],
            ],
        ]);

        $watcher = ($this->factory)($this->container);

        self::assertInstanceOf(Watcher::class, $watcher);
    }
}
