<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler;

use Htmx\Response\Header;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

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
        private readonly RouteCollectorInterface $routeCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $resources  = $this->aclRepository->fetchResources();
        $privileges = $this->aclRepository->fetchPrivileges();
        $roles      = $this->aclRepository->fetchRoles();

        // Build set of already-registered resource IDs
        $registeredIds = [];
        foreach ($resources as $resource) {
            $registeredIds[$resource->resourceId] = true;
        }

        // Unprotected = registered routes not yet opted in as ACL resources
        // Value is the route's allowed methods array for display
        $unprotected = [];
        foreach ($this->routeCollector->getRoutes() as $route) {
            $name = $route->getName();
            if ($name !== null && $name !== '' && ! isset($registeredIds[$name])) {
                $unprotected[$name] = $route->getAllowedMethods() ?? ['GET'];
            }
        }

        $response = new HtmlResponse($this->template->render('acl::admin-resources', [
            'resources'   => $resources,
            'privileges'  => $privileges,
            'unprotected' => $unprotected,
            'roles'       => $roles,
        ]));

        $commandResult = $request->getAttribute(CommandResult::class);
        if ($commandResult instanceof CommandResult && $commandResult->getStatus() === CommandStatus::Success) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}
