<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
            'dependencies'             => $this->getDependencies(),
            'router'                   => $this->getRouteProviders(),
            'templates'                => $this->getTemplates(),
            'authentication'           => $this->getAuthenticationConfig(),
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
                Middleware\RegistrationMiddleware::class            => Middleware\Container\RegistrationMiddlewareFactory::class,
                Repository\UserRepository::class                    => Repository\UserRepositoryFactory::class,
                RouteProvider::class                                => Container\RouteProviderFactory::class,
                RequestHandler\LoginHandler::class                  => RequestHandler\Container\LoginHandlerFactory::class,
                RequestHandler\LogoutHandler::class                 => RequestHandler\Container\LogoutHandlerFactory::class,
                RequestHandler\RegistrationHandler::class           => RequestHandler\Container\RegistrationHandlerFactory::class,
                RequestHandler\ResendVerificationHandler::class     => RequestHandler\Container\ResendVerificationHandlerFactory::class,
                RequestHandler\UserListHandler::class               => RequestHandler\Container\UserListHandlerFactory::class,                RequestHandler\VerifyEmailHandler::class             => RequestHandler\Container\VerifyEmailHandlerFactory::class,
                Listener\SendVerificationEmailListener::class        => Listener\Container\SendVerificationEmailListenerFactory::class,            ],
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
