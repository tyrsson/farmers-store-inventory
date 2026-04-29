<?php

declare(strict_types=1);

use User\Listener\SendVerificationEmailListener;
use Webware\CommandBus\Event\PostHandleEvent;

return [
    'listeners' => [
        PostHandleEvent::class => [
            ['listener' => SendVerificationEmailListener::class, 'priority' => 1],
        ],
    ],
];
