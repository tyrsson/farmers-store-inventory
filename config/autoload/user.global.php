<?php

declare(strict_types=1);

use Webware\UserManager\Event\SendVerificationEmailEvent;
use Webware\UserManager\Listener\SendVerificationEmailListener;

return [
    'listeners' => [
        SendVerificationEmailEvent::class => [
            ['listener' => SendVerificationEmailListener::class, 'priority' => 1],
        ],
    ],
    'user' => [
        /**
         * Base URL used when building the email verification link.
         * Override in a local.php file for production.
         */
        'base_url'                 => 'http://localhost:8080',
        'from_email'               => 'noreply@farmers-ims.local',
        'from_name'                => 'Farmers IMS',
        /**
         * How long (in seconds) a verification token remains valid.
         * Default: 86400 (24 hours).
         */
        'verification_token_ttl'   => 86400,
    ],
];
