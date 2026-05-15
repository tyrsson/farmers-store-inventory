<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\Acl\Widget\AclDashboardWidget;
use Webware\Admin\Event\RegisterWidgetEvent;

use function array_map;
use function array_sum;
use function count;

/**
 * Contributes the ACL Management widget to the admin dashboard.
 *
 * Invoked on RegisterWidgetEvent; fetches live stat counts from the
 * repository and registers an AclDashboardWidget with those counts.
 */
final class RegisterAclWidgetListener
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
    ) {}

    public function __invoke(RegisterWidgetEvent $event): void
    {
        $assertions = $this->aclRepository->fetchRuleAssertions();
        $event->registerWidget(new AclDashboardWidget(
            roleCount:      count($this->aclRepository->fetchRoles()),
            resourceCount:  count($this->aclRepository->fetchResources()),
            ruleCount:      count($this->aclRepository->fetchRules()),
            assertionCount: (int) array_sum(array_map('count', $assertions)),
            aclVersion:     $this->aclRepository->fetchVersion(),
        ));
    }
}
