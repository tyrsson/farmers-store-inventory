<?php

declare(strict_types=1);

namespace Mezzio\Async\HotCodeReload;

use Async\Channel;
use Async\FileSystemWatcher;
use Async\Scope;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_unique;
use function in_array;
use function is_dir;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * Watches PHP source files for changes and notifies the server to restart.
 *
 * Uses Async\FileSystemWatcher (non-recursive) across all enumerated
 * subdirectories, working around the TrueAsync bug where recursive mode
 * fails to deliver events for files in subdirectories.
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
     * Creates one non-recursive FileSystemWatcher per directory (enumerating
     * all subdirectories when recursive mode is requested). All watchers race
     * to send the first matching filename into a Channel(1); the winner
     * triggers the reload callback and cancels the remaining watchers.
     *
     * @param Scope    $scope    The server Scope that owns this coroutine.
     * @param callable $onReload Called once when a watched file changes.
     */
    public function startIn(Scope $scope, callable $onReload): void
    {
        $scope->spawn(function () use ($onReload): void {
            $dirs = $this->gatherDirectories();

            $this->logger->debug('HotCodeReload: watching', [
                'directories' => count($dirs),
                'paths'       => $this->paths,
            ]);

            // Channel(1): first matching file-change event wins; subsequent
            // sendAsync() calls on a full channel are silently dropped.
            $ch           = new Channel(1);
            $watcherScope = Scope::inherit();

            foreach ($dirs as $dir) {
                $watcherScope->spawn(static function () use ($dir, $ch): void {
                    $watcher = new FileSystemWatcher($dir, recursive: false);
                    foreach ($watcher as $event) {
                        $ext = pathinfo($event->filename ?? '', PATHINFO_EXTENSION);
                        if (in_array($ext, self::WATCHED_EXTENSIONS, strict: true)) {
                            $ch->sendAsync($event->filename);
                            return;
                        }
                    }
                });
            }

            $filename = $ch->recv();
            $watcherScope->cancel();

            $this->logger->info(
                'HotCodeReload: file changed — scheduling restart',
                ['file' => $filename],
            );

            $onReload();
        });
    }

    /**
     * Collects all directories to watch.
     *
     * When recursive mode is enabled, enumerates every subdirectory under
     * each configured root path. This is a one-time cost at startup that
     * works around the TrueAsync FileSystemWatcher recursive-mode bug.
     *
     * @return string[]
     */
    private function gatherDirectories(): array
    {
        $dirs = [];

        foreach ($this->paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $dirs[] = $path;

            if ($this->recursive) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $path,
                        RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        $dirs[] = $item->getPathname();
                    }
                }
            }
        }

        return array_unique($dirs);
    }
}
