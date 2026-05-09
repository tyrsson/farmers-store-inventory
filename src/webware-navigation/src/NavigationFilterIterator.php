<?php

declare(strict_types=1);


namespace Webware\Navigation;

use ArrayIterator;
use FilterIterator;
use Mezzio\Router\Route;
use Override;
use Webware\Acl\AclInterface;

use function is_array;
use function is_string;
use function in_array;

/**
 * Filters a list<Route> to those belonging to a given navigation identifier
 * that the current user's roles are ACL-permitted to access.
 *
 * Decouples ACL evaluation from the Navigation view helper — the helper only
 * iterates; this class decides what is visible.
 *
 * @extends FilterIterator<int, Route, ArrayIterator<int, Route>>
 */
final class NavigationFilterIterator extends FilterIterator
{
    /**
     * @param list<Route>  $routes
     * @param string[]     $roles
     */
    public function __construct(
        array $routes,
        private readonly string $navId,
        private readonly array $roles,
        private readonly AclInterface $acl,
    ) {
        parent::__construct(new ArrayIterator($routes));
    }

    #[Override]
    public function accept(): bool
    {
        /** @var Route $route */
        $route   = $this->current();
        $options = $route->getOptions();

        return self::belongsToNav($options, $this->navId)
            && $this->acl->isAllowedByRouteName($route->getName(), $this->roles);
    }

    /** @param array<string, mixed> $options */
    private static function belongsToNav(array $options, string $navId): bool
    {
        $nav = $options['navigation'] ?? null;

        if (is_string($nav)) {
            return $nav === $navId;
        }

        if (is_array($nav)) {
            return in_array($navId, $nav, true);
        }

        return false;
    }
}
