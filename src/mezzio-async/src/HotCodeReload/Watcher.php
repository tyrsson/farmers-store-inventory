<?php

declare(strict_types=1);

namespace Mezzio\Async\HotCodeReload;

use Async\Scope;
use Psr\Log\LoggerInterface;

use function array_merge;
use function fclose;
use function fgets;
use function in_array;
use function is_resource;
use function pathinfo;
use function proc_close;
use function proc_open;
use function proc_terminate;
use function rtrim;

use const PATHINFO_EXTENSION;

/**
 * Watches PHP source files for changes and notifies the server to restart.
 *
 * Uses inotifywait (from inotify-tools) to monitor filesystem events,
 * working around a bug in TrueAsync's FileSystemWatcher where recursive
 * mode fails to deliver events for files in subdirectories.
 *
 * Used exclusively in development. Spawn it into the server Scope via
 * {@see startIn()} so that it is automatically cancelled on shutdown.
 */
final readonly class Watcher
{
    /** @var string[] File extensions that trigger a reload (without leading dot). */
    private const array WATCHED_EXTENSIONS = ['php', 'phtml'];

    /**
     * @param string[] $paths     Absolute filesystem paths to watch.
     * @param bool     $recursive Watch subdirectories recursively.
     */
    public function __construct(
        private array $paths,
        private bool $recursive,
        private LoggerInterface $logger,
    ) {}

    /**
     * Spawns the file-watcher coroutine into $scope.
     *
     * Launches inotifywait as a subprocess and reads its stdout line by line
     * inside a TrueAsync coroutine (fgets suspends the coroutine, not the
     * process). On the first matching file event the subprocess is terminated,
     * the callback is invoked, and the coroutine exits.
     *
     * @param Scope    $scope    The server Scope that owns this coroutine.
     * @param callable $onReload Called once when a watched file changes.
     */
    public function startIn(Scope $scope, callable $onReload): void
    {
        $scope->spawn(function () use ($onReload): void {
            $cmd = array_merge(
                ['inotifywait', '-m', '-e', 'close_write,moved_to', '--format', '%f', '-q'],
                $this->recursive ? ['-r'] : [],
                $this->paths,
            );

            $process = proc_open($cmd, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ], $pipes);

            if ($process === false || ! is_resource($process)) {
                $this->logger->error(
                    'HotCodeReload: failed to start inotifywait — is inotify-tools installed?'
                );
                return;
            }

            fclose($pipes[0]);
            $stdout = $pipes[1];

            $this->logger->debug('HotCodeReload: watching', [
                'paths'     => $this->paths,
                'recursive' => $this->recursive,
            ]);

            try {
                while (($line = fgets($stdout)) !== false) {
                    $filename  = rtrim($line);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);

                    if (in_array($extension, self::WATCHED_EXTENSIONS, strict: true)) {
                        $this->logger->info(
                            'HotCodeReload: file changed — scheduling restart',
                            ['file' => $filename],
                        );
                        $onReload();
                        return;
                    }
                }
            } finally {
                fclose($stdout);
                proc_terminate($process);
                proc_close($process);
            }
        });
    }
}
