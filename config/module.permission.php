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
        [
            'title'      => 'Admin signature check',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'signature',
            'handler'    => 'check',
            'permission' => 'admin-core-signature-check',
            'role'       => [
                'admin',
            ],
        ],
        [
            'title'      => 'Admin signature update',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'signature',
            'handler'    => 'update',
            'permission' => 'admin-core-signature-update',
            'role'       => [
                'admin',
            ],
        ],
        [
            'title'      => 'Admin module list',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'module',
            'handler'    => 'list',
            'permission' => 'admin-core-module-list',
            'role'       => [
                'admin',
            ],
        ],
        [
            'title'      => 'Admin slug update',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'slug',
            'handler'    => 'update',
            'permission' => 'admin-core-slug-update',
            'role'       => [
                'admin',
            ],
        ],
        [
            'title'      => 'System information',
            'module'     => 'core',
            'section'    => 'admin',
            'package'    => 'system',
            'handler'    => 'information',
            'permission' => 'admin-core-system-information',
            'role'       => [
                'admin',
            ],
        ]
    ],
];