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

namespace Webware\Acl\Authentication;

use Mezzio\Authentication\DefaultUser;
use Mezzio\Authentication\UserInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

/**
 * Replaces Mezzio\Authentication\DefaultUserFactory as the DI factory for
 * the UserInterface::class callable service.
 *
 * The returned callable creates DefaultUser instances. When called with an
 * empty $roles array (e.g. during an unauthenticated session restore), it
 * falls back to the configured base role so that every user object always
 * carries at least one role for ACL checks.
 */
final class DefaultUserFactory
{
    public function __invoke(ContainerInterface $container): callable
    {
        $config   = $container->get('config');
        $baseRole = (string) ($config['webware-acl']['base_role'] ?? 'guest');

        return static function (
            string $identity,
            array $roles = [],
            array $details = [],
        ) use ($baseRole): UserInterface {
            Assert::allString($roles);
            Assert::isMap($details);

            if (empty($roles)) {
                $roles = [$baseRole];
            }

            return new DefaultUser($identity, $roles, $details);
        };
    }
}
