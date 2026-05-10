<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler;

use Htmx\Response\Header;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\Acl\Admin\WriteResult;

use function json_encode;

/**
 * Handles GET /admin/access/roles — list all roles with parent info and user counts.
 * Handles POST /admin/access/roles — create a new role.
 *
 * TODO: implement POST (task 2.7).
 */
final class RoleListHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
        private readonly TemplateRendererInterface $template,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $roles       = $this->aclRepository->fetchRoles();
        $roleParents = $this->aclRepository->fetchRoleParents();

        $response = new HtmlResponse($this->template->render('acl::admin-roles', [
            'roles'       => $roles,
            'roleParents' => $roleParents,
        ]));

        if ($request->getAttribute(WriteResult::Success->value) === true) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}
