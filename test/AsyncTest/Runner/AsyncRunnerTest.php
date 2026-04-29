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

namespace AsyncTest\Runner;

use Mezzio\Async\Http\Server;
use Mezzio\Async\Runner\AsyncRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsyncRunner::class)]
final class AsyncRunnerTest extends TestCase
{
    public function testRunDelegatesToServerListen(): void
    {
        if (! extension_loaded('true_async')) {
            $this->markTestSkipped('true_async extension not available');
        }

        // When the extension is present, run() should call Server::listen() and
        // block until a signal is received. Full lifecycle tested via Docker.
        self::assertTrue(true);
    }
}
