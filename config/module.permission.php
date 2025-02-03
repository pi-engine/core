<?php

return [
    'admin' => [
        [
            'title'      => 'Admin config list',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'config',
            'handler'    => 'list',
            'permission' => 'admin-core-config-list',
            'role'       => [
                'admin',
            ],
        ],
        [
            'title'      => 'Admin config update',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'config',
            'handler'    => 'update',
            'permission' => 'admin-core-config-update',
            'role'       => [
                'admin',
            ],
        ],
    ],
];