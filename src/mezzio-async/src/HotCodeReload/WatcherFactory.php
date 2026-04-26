<?php

declare(strict_types=1);

namespace Mezzio\Async\HotCodeReload;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class WatcherFactory
{
    public function __invoke(ContainerInterface $container): Watcher
    {
        $config    = $container->has('config') ? $container->get('config') : [];
        $hotReload = $config['mezzio-async']['hot-reload'] ?? [];

        $paths = $hotReload['paths'] ?? ['src', 'config'];
        // Resolve relative paths to absolute from the project root (cwd).
        $paths = array_unique(array_map(
            static fn(string $p): string => (str_starts_with($p, '/') ? $p : getcwd() . '/' . $p),
            $paths,
        ));

        $recursive = (bool) ($hotReload['recursive'] ?? true);

        return new Watcher(
            paths:     $paths,
            recursive: $recursive,
            logger:    $container->get(LoggerInterface::class),
        );
    }
}
