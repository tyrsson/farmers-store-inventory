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
use function socket_import_stream;
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
use const TCP_NODELAY;

/**
 * Owns the TCP socket, TrueAsync scheduler entry, Scope lifecycle,
 * accept loop, and signal handling.
 *
 * The connection handler callable is responsible only for what to do
 * with each accepted connection.
 *
 * When a {@see Watcher} is provided (development only), file changes trigger
 * a graceful restart instead of a clean exit.
 */
final readonly class Server
{
    public function __construct(
        private string $host,
        private int $port,
        private LoggerInterface $logger,
        private ?Watcher $watcher = null,
    ) {}

    /**
     * Binds the socket, enters the TrueAsync scheduler, and calls
     * $connectionHandler for every accepted connection.
     *
     * Blocks until SIGTERM or SIGINT is received, then drains gracefully.
     * When a {@see Watcher} is configured and detects a PHP file change the
     * process is restarted in-place via pcntl_exec() after draining.
     *
     * @param callable(mixed $conn, string $peerName): void $connectionHandler
     */
    public function listen(callable $connectionHandler): void
    {
        $server = $this->createServerSocket();

        $this->logger->notice(
            sprintf('mezzio-async listening on http://%s:%d', $this->host, $this->port)
        );

        // Capture argv before entering the scheduler for use in pcntl_exec restart.
        // Use a flag so pcntl_exec is called AFTER await() returns — i.e. after the
        // TrueAsync scheduler has fully exited and released all internal FD references.
        // Calling pcntl_exec from inside the scheduler leaves the scheduler's C-level
        // socket references open, causing "Address already in use" in the new process.
        $argv    = $_SERVER['argv'] ?? [];
        $restart = false;

        await(spawn(function () use ($server, $connectionHandler, &$restart): void {
            $scope = new Scope();

            $scope->setExceptionHandler(function (Throwable $e): void {
                $this->logger->error('Unhandled connection error', ['exception' => $e]);
            });

            // A Channel with capacity 1 acts as a first-wins trigger.
            // The signal listener sends false (clean shutdown); the file watcher
            // sends true (restart). Only the first message fits — subsequent
            // sendAsync() calls on a full channel return false and are silently
            // dropped, preventing duplicate restarts.
            $shutdownCh = new Channel(1);

            // Signal listener coroutine — competes with the watcher for $shutdownCh.
            $scope->spawn(function () use ($shutdownCh): void {
                await_any_or_fail([
                    signal(Signal::SIGTERM),
                    signal(Signal::SIGINT),
                ]);
                $shutdownCh->sendAsync(false);
            });

            // Accept loop coroutine
            $scope->spawn(function () use ($server, $scope, $connectionHandler): void {
                try {
                    while (true) {
                        $peerName = '';
                        $conn     = @stream_socket_accept($server, -1, $peerName);

                        if ($conn === false) {
                            // Accept interrupted — scope is being cancelled
                            break;
                        }

                        // TCP_NODELAY is not inherited from the listening socket on Linux.
                        // Without it, Nagle's algorithm + client delayed-ACK (~40 ms) adds
                        // ~39 ms latency per keep-alive response when the emitter does
                        // multiple small fwrite() calls (status line, headers, body).
                        $sock = socket_import_stream($conn);
                        if ($sock !== false) {
                            socket_set_option($sock, SOL_TCP, TCP_NODELAY, 1);
                        }

                        $scope->spawn($connectionHandler, $conn, $peerName);
                    }
                } finally {
                    fclose($server);
                }
            });

            // Optionally start the hot-reload watcher (development only).
            // On file change it sends true to $shutdownCh, racing the signal listener.
            $this->watcher?->startIn($scope, static function () use ($shutdownCh): void {
                $shutdownCh->sendAsync(true);
            });

            // Block until the first message arrives — either a signal or a file change.
            $restart = $shutdownCh->recv();

            $this->logger->notice(
                $restart
                    ? 'File change detected, restarting…'
                    : 'Shutdown signal received, draining connections…'
            );

            $scope->cancel();

            $scope->awaitAfterCancellation(
                errorHandler: fn(Throwable $e) => $this->logger->error(
                    'Error during shutdown drain',
                    ['exception' => $e]
                )
            );

            // Do NOT call pcntl_exec here — let the scheduler exit cleanly first.
        }));

        // The TrueAsync scheduler has fully exited. All internal FD references are
        // released. It is now safe to close the socket and exec the new process.
        if (is_resource($server)) {
            fclose($server);
        }

        if ($restart) {
            pcntl_exec(PHP_BINARY, $argv);
        }

        $this->logger->notice('mezzio-async stopped');
    }

    /**
     * Creates the listening TCP socket with SO_REUSEPORT set.
     *
     * SO_REUSEPORT lets the new process (after pcntl_exec) bind to the same
     * address:port while the old process's socket still exists in the kernel.
     * This is necessary because TrueAsync's io_uring/epoll layer may hold an
     * internal reference to the fd that survives PHP's fclose(), causing
     * "Address already in use" in the restarted process.
     *
     * @return resource
     */
    private function createServerSocket(): mixed
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($sock === false) {
            throw new RuntimeException(
                sprintf('Cannot create socket for %s:%d', $this->host, $this->port)
            );
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

        if (! socket_listen($sock, 4096)) {
            throw new RuntimeException(sprintf(
                'Cannot listen on %s:%d — %s',
                $this->host,
                $this->port,
                socket_strerror(socket_last_error($sock)),
            ));
        }

        $stream = socket_export_stream($sock);

        if ($stream === false) {
            throw new RuntimeException(
                sprintf('Cannot export socket as stream for %s:%d', $this->host, $this->port)
            );
        }

        return $stream;
    }
}
