<?php

declare(strict_types=1);

namespace Webware\Admin\Widget;

use FilterIterator;
use Iterator;
use Webware\Acl\AclInterface;

/**
 * Wraps an iterator of WidgetInterface instances and accepts only those
 * the current user's roles are permitted to see, according to the ACL.
 *
 * @extends FilterIterator<int, WidgetInterface, Iterator<int, WidgetInterface>>
 */
final class AclWidgetFilterIterator extends FilterIterator
{
    /**
     * @param Iterator<int, WidgetInterface> $iterator
     * @param string[]                       $roles    Current user's roles
     */
    public function __construct(
        Iterator $iterator,
        private readonly AclInterface $acl,
        private readonly array $roles,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $widget = $this->current();

        if (! $widget instanceof WidgetInterface) {
            return false;
        }

        return $this->acl->isAllowed($this->roles, $widget->resourceId, $widget->privilege);
    }
}
