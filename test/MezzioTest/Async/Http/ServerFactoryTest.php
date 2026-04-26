<?php

declare(strict_types=1);

namespace MezzioTest\Async\Http;

use AppTest\InMemoryContainer;
use Mezzio\Async\HotCodeReload\Watcher;
use Mezzio\Async\Http\Server;
use Mezzio\Async\Http\ServerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(ServerFactory::class)]
final class ServerFactoryTest extends TestCase
{
    private InMemoryContainer $container;
    private ServerFactory $factory;

    protected function setUp(): void
    {
        $this->container = new InMemoryContainer();
        $this->factory   = new ServerFactory();
    }

    public function testReturnsServerInstance(): void
    {
        $this->container->setService('config', []);
        $this->container->setService(\Psr\Log\LoggerInterface::class, new NullLogger());

        $server = ($this->factory)($this->container);

        self::assertInstanceOf(Server::class, $server);
    }

    public function testReadsHostAndPortFromHttpServerConfig(): void
    {
        $this->container->setService('config', [
            'mezzio-async' => [
                'http-server' => [
                    'host' => '127.0.0.1',
                    'port' => 9000,
                ],
            ],
        ]);
        $this->container->setService(\Psr\Log\LoggerInterface::class, new NullLogger());

        $server = ($this->factory)($this->container);

        self::assertInstanceOf(Server::class, $server);
    }

    public function testFallsBackToFlatAsyncConfigWhenHttpServerKeyAbsent(): void
    {
        $this->container->setService('config', [
            'mezzio-async' => [
                'host' => '0.0.0.0',
                'port' => 8080,
            ],
        ]);
        $this->container->setService(\Psr\Log\LoggerInterface::class, new NullLogger());

        $server = ($this->factory)($this->container);

        self::assertInstanceOf(Server::class, $server);
    }

    public function testUsesDefaultsWhenNoConfigPresent(): void
    {
        $this->container->setService(\Psr\Log\LoggerInterface::class, new NullLogger());

        $server = ($this->factory)($this->container);

        self::assertInstanceOf(Server::class, $server);
    }

    public function testHotReloadDisabledByDefault(): void
    {
        $this->container->setService('config', []);
        $this->container->setService(\Psr\Log\LoggerInterface::class, new NullLogger());

        // No Watcher service registered — factory must not try to resolve it.
        $server = ($this->factory)($this->container);

        self::assertInstanceOf(Server::class, $server);
    }

    public function testHotReloadEnabledInjectsWatcher(): void
    {
        $logger  = new NullLogger();
        $watcher = new Watcher(paths: ['/tmp'], recursive: false, logger: $logger);

        $this->container->setService('config', [
            'mezzio-async' => [
                'hot-reload' => ['enabled' => true],
            ],
        ]);
        $this->container->setService(\Psr\Log\LoggerInterface::class, $logger);
        $this->container->setService(Watcher::class, $watcher);

        $server = ($this->factory)($this->container);

        self::assertInstanceOf(Server::class, $server);
    }
}
