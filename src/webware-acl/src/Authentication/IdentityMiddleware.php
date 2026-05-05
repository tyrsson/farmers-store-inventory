<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Authentication;

use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionMiddleware;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Resolves the current identity and attaches a UserInterface to every request.
 *
 * Two states only:
 *  - Session contains UserInterface data → deserialise and set the real user
 *  - No session data                     → set a guest user via the configured base role
 *
 * Always calls the next handler — access decisions are AclMiddleware's job.
 * Pipe this once in the global pipeline, after SessionMiddleware.
 */
final class IdentityMiddleware implements MiddlewareInterface
{
    /** @var callable(string, string[], array<string, mixed>): UserInterface */
    private $userFactory;

    /**
     * @param callable(string, string[], array<string, mixed>): UserInterface $userFactory
     */
    public function __construct(callable $userFactory, private readonly string $baseRole)
    {
        $this->userFactory = $userFactory;
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if ($session === null || ! $session->has(UserInterface::class)) {
            $request = $request->withAttribute(
                UserInterface::class,
                ($this->userFactory)('guest', [$this->baseRole], []),
            );

            return $handler->handle($request);
        }

        /** @var array{identity: string, roles: string[], details: array<string, mixed>} */
        $data = $session->get(UserInterface::class);

        return $handler->handle(
            $request->withAttribute(
                UserInterface::class,
                ($this->userFactory)(
                    $data['identity'] ?? 'guest',
                    $data['roles']    ?? [$this->baseRole],
                    $data['details']  ?? [],
                ),
            ),
        );
    }
}
