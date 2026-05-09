<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Webware\Acl\Widget\AclDashboardWidget;
use Webware\Admin\Event\RegisterWidgetEvent;

/**
 * Contributes the ACL Management widget to the admin dashboard.
 *
 * Invoked on RegisterWidgetEvent; registers AclDashboardWidget so that
 * the admin dashboard displays the ACL management summary card.
 */
final class RegisterAclWidgetListener
{
    public function __invoke(RegisterWidgetEvent $event): void
    {
        $event->registerWidget(new AclDashboardWidget());
    }
}
