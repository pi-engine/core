<?php

namespace Core;

use Laminas\Router\Http\Literal;

return [
    'service_manager' => [
        'aliases'   => [],
        'factories' => [],
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
