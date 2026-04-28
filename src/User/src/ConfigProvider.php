<?php

declare(strict_types=1);

namespace User;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\PhpSession;
use Mezzio\Authentication\UserRepositoryInterface;
use User\Repository\UserRepositoryInterface as UserRepositoryContract;
use Webware\CommandBus\CommandBusInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'         => $this->getDependencies(),
            'router'               => $this->getRouteProviders(),
            'templates'            => $this->getTemplates(),
            'mezzio-authentication'       => $this->getAuthenticationConfig(),
            CommandBusInterface::class => [
                'command_map' => $this->getCommandMap(),
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                // Bind mezzio-authentication interfaces to our implementations
                UserRepositoryInterface::class => UserRepositoryContract::class,
                UserRepositoryContract::class  => Repository\UserRepository::class,
                AuthenticationInterface::class => PhpSession::class,
            ],
            'factories' => [
                Admin\RequestHandler\CreateUserHandler::class       => Admin\RequestHandler\Container\CreateUserHandlerFactory::class,
                Admin\RequestHandler\UpdateUserHandler::class       => Admin\RequestHandler\Container\UpdateUserHandlerFactory::class,
                Admin\RequestHandler\ToggleUserActiveHandler::class => Admin\RequestHandler\Container\ToggleUserActiveHandlerFactory::class,
                CommandHandler\SaveUserHandler::class               => CommandHandler\Container\SaveUserHandlerFactory::class,
                Repository\UserRepository::class                    => Repository\UserRepositoryFactory::class,
                RouteProvider::class                                => Container\RouteProviderFactory::class,
                RequestHandler\LoginHandler::class                  => RequestHandler\Container\LoginHandlerFactory::class,
                RequestHandler\LogoutHandler::class                 => RequestHandler\Container\LogoutHandlerFactory::class,
                RequestHandler\UserListHandler::class               => RequestHandler\Container\UserListHandlerFactory::class,
            ],
        ];
    }

    /** @return array<class-string, class-string> */
    public function getCommandMap(): array
    {
        return [
            Command\SaveUserCommand::class => CommandHandler\SaveUserHandler::class,
        ];
    }

    public function getRouteProviders(): array
    {
        return [
            'route-providers' => [
                RouteProvider::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'user' => [__DIR__ . '/../templates/user'],
            ],
        ];
    }

    public function getAuthenticationConfig(): array
    {
        return [
            'redirect' => '/login',
            'username' => 'email',
            'password' => 'password',
        ];
    }
}
