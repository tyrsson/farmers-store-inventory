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
 * Handles GET /admin/access/resources — list all resources with their privileges.
 * Handles POST /admin/access/resources — create a new resource.
 *
 * TODO: implement POST (task 2.9).
 */
final class ResourceListHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
        private readonly TemplateRendererInterface $template,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $resources  = $this->aclRepository->fetchResources();
        $privileges = $this->aclRepository->fetchPrivileges();

        $response = new HtmlResponse($this->template->render('acl::admin-resources', [
            'resources'  => $resources,
            'privileges' => $privileges,
        ]));

        if ($request->getAttribute(WriteResult::Success->value) === true) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}
