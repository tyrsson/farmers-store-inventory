<?php

declare(strict_types=1);

use Webware\UserManager\Listener\SendVerificationEmailListener;
use Webware\CommandBus\Event\PostHandleEvent;

return [
    'listeners' => [
        PostHandleEvent::class => [
            ['listener' => SendVerificationEmailListener::class, 'priority' => 1],
        ],
    ],
];
