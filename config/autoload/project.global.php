<?php

declare(strict_types=1);

use Axleus\Mailer\Adapter\MessageInterface;

return [
    MessageInterface::class => [
        'to'         => 'jsmith@webinertia.net',
        'from'       => 'contact@webinertia.dev',
        'subject'    => 'Webinertia Project Request',
        'auto_reply' => true,
    ],
    'view_helper_config' => [
        'asset' => [
            'resource_map' => [
                'debug.js'     => 'assets/js/debug.js',
                'messenger.js' => 'assets/js/system.messenger.js',
            ],
        ],
    ],
];