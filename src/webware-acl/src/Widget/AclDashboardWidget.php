<?php

declare(strict_types=1);


namespace Webware\Acl\Widget;

use Override;
use Webware\Admin\Widget\WidgetInterface;

/**
 * Admin dashboard widget contributed by webware-acl.
 *
 * Links to the ACL Management pages (roles, resources, rules, routes).
 * Visible to any role with 'read' access to 'admin.dashboard'.
 */
final class AclDashboardWidget implements WidgetInterface
{
    public string $title      { get => 'ACL Management'; }

    public string $resourceId { get => 'admin.dashboard'; }

    public string $privilege  { get => 'read'; }

    public string $template   { get => 'acl::admin-widget'; }

    public int    $order      { get => 10; }

    #[Override]
    public function getResourceId(): string
    {
        return $this->resourceId;
    }
}
