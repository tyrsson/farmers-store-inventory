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

namespace User\Listener;

use Axleus\Mailer\MailerInterface;
use Override;
use User\Command\SaveUserCommand;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\Event\EventInterface;
use Webware\CommandBus\Event\ListenerInterface;
use Webware\CommandBus\Event\PostHandleEvent;

final class SendVerificationEmailListener implements ListenerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly string $baseUrl,
    ) {}

    #[Override]
    public function __invoke(EventInterface $event): void
    {
        if (! $event instanceof PostHandleEvent) {
            return;
        }

        $commandResult = $event->getCommand();

        if (! $commandResult instanceof CommandResultInterface) {
            return;
        }

        if ($commandResult->getStatus() !== CommandStatus::Success) {
            return;
        }

        $command = $commandResult->getCommand();

        if (! $command instanceof SaveUserCommand) {
            return;
        }

        $token           = (string) $commandResult->getResult();
        $verificationUrl = rtrim($this->baseUrl, '/') . '/verify-email/' . $token;

        $adapter = $this->mailer->getAdapter();

        if ($adapter === null) {
            return;
        }

        $adapter
            ->from($this->fromEmail, $this->fromName)
            ->to($command->email, $command->firstName . ' ' . $command->lastName)
            ->subject('Verify your Farmers IMS account')
            ->isHtml(true)
            ->body(
                '<p>Hello ' . htmlspecialchars($command->firstName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Thank you for registering. Please verify your email address by clicking the link below.</p>'
                . '<p><a href="' . htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') . '">Verify my email</a></p>'
                . '<p>This link expires in 24 hours.</p>'
            )
            ->altBody(
                'Hello ' . $command->firstName . ",\n\n"
                . "Please verify your email address by visiting the following link:\n"
                . $verificationUrl . "\n\n"
                . "This link expires in 24 hours.\n"
            );

        $this->mailer->send();
    }
}
