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

namespace User\RequestHandler;

use Axleus\Message\SystemMessengerInterface;
use DateTimeImmutable;
use Htmx\Request\Header as HtmxRequestHeader;
use Htmx\Response\Header as HtmxResponseHeader;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Repository\UserRepositoryInterface;

use function is_string;

final class VerifyEmailHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly int $tokenTtl,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tokenAttr = $request->getAttribute('token');
        $token     = is_string($tokenAttr) ? $tokenAttr : '';

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        if ($token === '') {
            $messenger?->danger('Invalid verification link.', hops: 1, now: false);

            return $request->hasHeader(HtmxRequestHeader::Request->value)
                ? new HtmlResponse('', 200, [HtmxResponseHeader::Location->value => '/login'])
                : new RedirectResponse('/login');
        }

        $user = $this->users->findByVerificationToken($token);

        if ($user === null) {
            $messenger?->danger('Invalid or already used verification link.', hops: 1, now: false);

            return $request->hasHeader(HtmxRequestHeader::Request->value)
                ? new HtmlResponse('', 200, [HtmxResponseHeader::Location->value => '/login'])
                : new RedirectResponse('/login');
        }

        if ($user->tokenCreatedAt !== null) {
            $age = (new DateTimeImmutable())->getTimestamp() - $user->tokenCreatedAt->getTimestamp();

            if ($age > $this->tokenTtl) {
                $messenger?->danger('Verification link has expired. Please register again.', hops: 1, now: false);

                return $request->hasHeader(HtmxRequestHeader::Request->value)
                    ? new HtmlResponse('', 200, [HtmxResponseHeader::Location->value => '/register'])
                    : new RedirectResponse('/register');
            }
        }

        $this->users->update($user->id, [
            'active'             => 1,
            'verification_token' => null,
            'token_created_at'   => null,
        ]);

        $messenger?->success('Email verified! You may now sign in.', hops: 1, now: false);

        return $request->hasHeader(HtmxRequestHeader::Request->value)
            ? new HtmlResponse('', 200, [HtmxResponseHeader::Location->value => '/login'])
            : new RedirectResponse('/login');
    }
}
