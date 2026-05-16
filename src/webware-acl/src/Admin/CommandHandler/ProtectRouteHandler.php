<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\CommandHandler;

use Override;
use Webware\Acl\Admin\Command\ProtectRouteCommand;
use Webware\Acl\Cache\AclCacheInterface;
use Webware\Acl\PrivilegeInterface;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function assert;
use function ucfirst;

final class ProtectRouteHandler implements CommandHandlerInterface
{
    private const array METHOD_PRIVILEGE_MAP = [
        'GET'    => PrivilegeInterface::READ,
        'POST'   => PrivilegeInterface::CREATE,
        'PUT'    => PrivilegeInterface::UPDATE,
        'PATCH'  => PrivilegeInterface::UPDATE,
        'DELETE' => PrivilegeInterface::DELETE,
    ];

    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
        private readonly AclCacheInterface $aclCache,
    ) {}

    #[Override]
    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof ProtectRouteCommand);

        $resourcePk = $this->aclRepository->saveResource($command->routeName, $command->routeName);

        // Derive unique privileges from allowed methods and insert each, collecting PKs
        $privileges = array_unique(array_filter(array_map(
            static fn(string $method): string => self::METHOD_PRIVILEGE_MAP[$method] ?? '',
            $command->allowedMethods,
        )));

        $privilegePks = [];
        foreach ($privileges as $privilege) {
            $privilegePks[] = $this->aclRepository->insertPrivilege($resourcePk, $privilege, ucfirst($privilege));
        }

        // Create allow rules for each selected role × privilege using cache for role lookup
        if ($command->roles !== []) {
            $cached  = $this->aclCache->get();
            $roleMap = [];
            if ($cached !== null) {
                foreach ($cached['roles'] as $row) {
                    $roleMap[(string) $row['role_id']] = (int) $row['id'];
                }
            }

            foreach ($command->roles as $roleId) {
                $rolePk = $roleMap[$roleId] ?? null;
                if ($rolePk === null) {
                    continue;
                }
                foreach ($privilegePks as $privilegePk) {
                    $this->aclRepository->saveRule($rolePk, $resourcePk, $privilegePk, 'allow');
                }
            }
        }

        $this->aclRepository->incrementVersion();

        return new CommandResult($command, CommandStatus::Success, $resourcePk);
    }
}
