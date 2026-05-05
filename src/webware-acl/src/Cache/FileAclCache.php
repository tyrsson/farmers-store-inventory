<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Cache;

use Override;
use Webware\Acl\Exception\RuntimeException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function mkdir;
use function serialize;
use function sprintf;
use function unlink;
use function unserialize;

use const LOCK_EX;

final class FileAclCache implements AclCacheInterface
{
    private readonly string $filePath;

    public function __construct(string $cacheDir = 'data/cache')
    {
        $this->filePath = $cacheDir . '/acl.cache';
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function get(): ?array
    {
        if (! file_exists($this->filePath)) {
            return null;
        }

        $raw  = file_get_contents($this->filePath);
        $data = unserialize($raw === false ? '' : $raw);

        return is_array($data) ? $data : null;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function set(array $data): void
    {
        $dir = dirname($this->filePath);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true)) {
                throw new RuntimeException(
                    sprintf('Unable to create ACL cache directory: %s', $dir),
                );
            }
        }

        file_put_contents($this->filePath, serialize($data), LOCK_EX);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function invalidate(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
