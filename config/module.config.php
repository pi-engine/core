<?php

namespace Pi\Core;

use Laminas\Router\Http\Literal;

return [
    'service_manager' => [
        'aliases'   => [],
        'factories' => [
            Security\Account\AccountLoginAttempts::class => Factory\Security\AccountLoginAttemptsFactory::class,
            Security\Account\AccountLocked::class        => Factory\Security\AccountLockedFactory::class,
            Middleware\SecurityMiddleware::class         => Factory\Middleware\SecurityMiddlewareFactory::class,
        ],
    ],
    'router'          => [
        'routes' => [
            // Admin section
            'admin_user' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/admin/core',
                    'defaults' => [],
                ],
                'child_routes' => [],
            ],
        ],
    ],
    'view_manager'    => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
