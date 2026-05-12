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
use Webware\Acl\AclBuilder;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

use function array_diff;
use function array_fill_keys;
use function array_keys;
use function json_encode;

/**
 * Handles GET /admin/access/routes — list all route mappings with a diff
 * against the registered routes so unmapped and orphaned entries are visible.
 * Handles POST /admin/access/routes — add/update a route mapping.
 * Handles DELETE /admin/access/routes/{route_name} — remove a route mapping.
 */
final class RouteMapManagerHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
        private readonly TemplateRendererInterface $template,
        private readonly RouteCollectorInterface $routeCollector,
        private readonly AclBuilder $aclBuilder,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dbMappings        = $this->aclRepository->fetchRouteMappings();
        $effectiveMappings = $this->aclBuilder->getRouteMappings();
        $resources         = $this->aclRepository->fetchResources();
        $privileges        = $this->aclRepository->fetchPrivileges();

        // Build the set of all registered route names
        $registeredNames = [];
        foreach ($this->routeCollector->getRoutes() as $route) {
            $name = $route->getName();
            if ($name !== null && $name !== '') {
                $registeredNames[$name] = true;
            }
        }

        // Mapped (DB) — in DB and route exists in router
        $mapped = [];
        // Orphaned — in DB but route no longer registered
        $orphaned = [];
        foreach ($dbMappings as $routeName => $mapping) {
            if (isset($registeredNames[$routeName])) {
                $mapped[$routeName] = $mapping;
            } else {
                $orphaned[$routeName] = $mapping;
            }
        }

        // Listener-mapped — in effective set but not in DB, and route exists
        $listenerMapped = [];
        foreach ($effectiveMappings as $routeName => $mapping) {
            if (! isset($dbMappings[$routeName]) && isset($registeredNames[$routeName])) {
                $listenerMapped[$routeName] = $mapping;
            }
        }

        // Unmapped — registered route not in effective mappings at all
        $unmappedNames = array_diff(
            array_keys($registeredNames),
            array_keys($effectiveMappings),
        );
        $unmapped = array_fill_keys($unmappedNames, true);

        $response = new HtmlResponse($this->template->render('acl::admin-route-map', [
            'mappings'       => $dbMappings,
            'mapped'         => $mapped,
            'listenerMapped' => $listenerMapped,
            'unmapped'       => $unmapped,
            'orphaned'       => $orphaned,
            'resources'      => $resources,
            'privileges'     => $privileges,
        ]));

        $commandResult = $request->getAttribute(CommandResult::class);
        if ($commandResult instanceof CommandResult && $commandResult->getStatus() === CommandStatus::Success) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}
