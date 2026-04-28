<?php

declare(strict_types=1);

namespace Mezzio\Async\Http;

use Async\Channel;
use Async\Scope;
use Async\Signal;
use Mezzio\Async\HotCodeReload\Watcher;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function Async\await;
use function Async\await_any_or_fail;
use function Async\signal;
use function Async\spawn;
use function fclose;
use function is_resource;
use function pcntl_exec;
use function socket_bind;
use function socket_create;
use function socket_export_stream;
use function socket_last_error;
use function socket_listen;
use function socket_set_option;
use function socket_strerror;
use function sprintf;
use function stream_socket_accept;

use const AF_INET;
use const PHP_BINARY;
use const SOCK_STREAM;
use const SOL_SOCKET;
use const SOL_TCP;
use const SO_REUSEADDR;
use const SO_REUSEPORT;

final readonly class Server
{
    public function __construct(
        private string $host,
        private int $port,
        private LoggerInterface $logger,
        private ?Watcher $watcher = null,
    ) {}

    public function listen(callable $connectionHandler): void
    {
        $server = $this->createServerSocket();

        $this->logger->notice(sprintf('mezzio-async listening on http://%s:%d', $this->host, $this->port));

        $argv    = $_SERVER['argv'] ?? [];
        $restart = false;

        await(spawn(function () use ($server, $connectionHandler, &$restart): void {
            $scope = new Scope();

            $scope->setExceptionHandler(function (Throwable $e): void {
                $this->logger->error('Unhandled connection error', ['exception' => $e]);
            });

            // Channel(1): first write wins — signal sends false, watcher sends true.
            $shutdownCh = new Channel(1);

            $scope->spawn(function () use ($shutdownCh): void {
                await_any_or_fail([signal(Signal::SIGTERM), signal(Signal::SIGINT)]);
                $shutdownCh->sendAsync(false);
            });

            $scope->spawn(function () use ($server, $scope, $connectionHandler): void {
                try {
                    while (true) {
                        $peerName = '';
                        $conn     = @stream_socket_accept($server, -1, $peerName);

                        if ($conn === false) {
                            break;
                        }

                        $scope->spawn($connectionHandler, $conn, $peerName);
                    }
                } finally {
                    if (is_resource($server)) {
                        fclose($server);
                    }
                }
            });

            $this->watcher?->startIn($scope, static function () use ($shutdownCh): void {
                $shutdownCh->sendAsync(true);
            });

            $restart = $shutdownCh->recv();

            $this->logger->notice($restart ? 'File change detected, restarting…' : 'Shutdown signal received, draining…');

            // Close socket first so stream_socket_accept(-1) returns false immediately.
            if (is_resource($server)) {
                fclose($server);
            }

            $scope->cancel();

            // For clean shutdown drain connections gracefully.
            // For hot-reload restart skip the drain — dev only, speed matters.
            if (! $restart) {
                $scope->awaitAfterCancellation(
                    errorHandler: fn(Throwable $e) => $this->logger->error('Drain error', ['exception' => $e]),
                );
            }
        }));

        if ($restart) {
            pcntl_exec(PHP_BINARY, $argv);
        }

        $this->logger->notice('mezzio-async stopped');
    }

    /** @return resource */
    private function createServerSocket(): mixed
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($sock === false) {
            throw new RuntimeException(sprintf('Cannot create socket for %s:%d', $this->host, $this->port));
        }

        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($sock, SOL_SOCKET, SO_REUSEPORT, 1);

        if (! socket_bind($sock, $this->host, $this->port)) {
            throw new RuntimeException(sprintf(
                'Cannot bind %s:%d — %s',
                $this->host,
                $this->port,
                socket_strerror(socket_last_error($sock)),
            ));
        }

        if (! socket_listen($sock, 512)) {
            throw new RuntimeException(sprintf(
                'Cannot listen on %s:%d — %s',
                $this->host,
                $this->port,
                socket_strerror(socket_last_error($sock)),
            ));
        }

        $stream = socket_export_stream($sock);

        if ($stream === false) {
            throw new RuntimeException(sprintf('Cannot export socket as stream for %s:%d', $this->host, $this->port));
        }

        return $stream;
    }
}

