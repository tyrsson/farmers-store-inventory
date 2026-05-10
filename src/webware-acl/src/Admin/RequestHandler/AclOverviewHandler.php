<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Repository\AclRepositoryInterface;

use function array_map;
use function array_sum;

/**
 * Handles GET /admin/access — ACL overview dashboard.
 */
final class AclOverviewHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
        private readonly TemplateRendererInterface $template,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $roles       = $this->aclRepository->fetchRoles();
        $roleParents = $this->aclRepository->fetchRoleParents();
        $resources   = $this->aclRepository->fetchResources();
        $rules       = $this->aclRepository->fetchRules();
        $assertions  = $this->aclRepository->fetchRuleAssertions();
        $mappings    = $this->aclRepository->fetchRouteMappings();

        return new HtmlResponse($this->template->render('acl::admin-acl', [
            'roles'           => $roles,
            'roleParents'     => $roleParents,
            'resources'       => $resources,
            'rules'           => $rules,
            'assertionCount'  => array_sum(array_map('count', $assertions)),
            'mappings'        => $mappings,
            'aclVersion'      => $this->aclRepository->fetchVersion(),
        ]));
    }
}
