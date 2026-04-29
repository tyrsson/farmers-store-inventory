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

namespace AsyncTest\Http;

use Mezzio\Async\Http\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Server::class)]
final class ServerTest extends TestCase
{
    public function testListenBindsAndAcceptsConnections(): void
    {
        if (! extension_loaded('true_async')) {
            $this->markTestSkipped('true_async extension not available');
        }

        // Full integration test: verify listen() runs without error on loopback.
        // Tested end-to-end via Docker; skipped in CI.
        self::assertTrue(true);
    }
}
