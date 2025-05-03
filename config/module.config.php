<?php

namespace Pi\Core;

use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;
use Pi\Core\Middleware\InstallerMiddleware;
use Pi\Core\Middleware\RequestPreparationMiddleware;
use Pi\Core\Middleware\SecurityMiddleware;
use Pi\User\Middleware\AuthenticationMiddleware;
use Pi\User\Middleware\AuthorizationMiddleware;

return [
    'service_manager' => [
        'aliases'   => [
            Repository\SignatureRepositoryInterface::class => Repository\SignatureRepository::class,
        ],
        'factories' => [
            Repository\SignatureRepository::class          => Factory\Repository\SignatureRepositoryFactory::class,
            Security\Account\AccountLoginAttempts::class   => Factory\Security\Account\AccountLoginAttemptsFactory::class,
            Security\Account\AccountLocked::class          => Factory\Security\Account\AccountLockedFactory::class,
            Security\Signature::class                      => Factory\Security\SignatureFactory::class,
            Installer\Install::class                       => Factory\Installer\InstallFactory::class,
            Installer\Update::class                        => Factory\Installer\UpdateFactory::class,
            Installer\Remove::class                        => Factory\Installer\RemoveFactory::class,
            Middleware\SecurityMiddleware::class           => Factory\Middleware\SecurityMiddlewareFactory::class,
            Middleware\InstallerMiddleware::class          => Factory\Middleware\InstallerMiddlewareFactory::class,
            Middleware\ErrorMiddleware::class              => Factory\Middleware\ErrorMiddlewareFactory::class,
            Middleware\RequestPreparationMiddleware::class => Factory\Middleware\RequestPreparationMiddlewareFactory::class,
            Service\InstallerService::class                => Factory\Service\InstallerServiceFactory::class,
            Service\ConfigService::class                   => Factory\Service\ConfigServiceFactory::class,
            Service\CacheService::class                    => Factory\Service\CacheServiceFactory::class,
            Service\UtilityService::class                  => Factory\Service\UtilityServiceFactory::class,
            Service\TranslatorService::class               => Factory\Service\TranslatorServiceFactory::class,
            Service\SignatureService::class                => Factory\Service\SignatureServiceFactory::class,
            Service\CsrfService::class                     => Factory\Service\CsrfServiceFactory::class,
            Handler\ErrorHandler::class                    => Factory\Handler\ErrorHandlerFactory::class,
            Handler\InstallerHandler::class                => Factory\Handler\InstallerHandlerFactory::class,
            Handler\Admin\Config\ListHandler::class        => Factory\Handler\Admin\Config\ListHandlerFactory::class,
            Handler\Admin\Config\UpdateHandler::class      => Factory\Handler\Admin\Config\UpdateHandlerFactory::class,
            Handler\Admin\Signature\CheckHandler::class    => Factory\Handler\Admin\Signature\CheckHandlerFactory::class,
            Handler\Admin\Signature\UpdateHandler::class   => Factory\Handler\Admin\Signature\UpdateHandlerFactory::class,
        ],
    ],
    'router'          => [
        'routes' => [
            // Admin section
            'admin_core' => [
                'type'         => Literal::class,
                'options'      => [
                    'route'    => '/admin/core',
                    'defaults' => [],
                ],
                'child_routes' => [
                    'config'    => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/config',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'list'   => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/list',
                                    'defaults' => [
                                        'title'      => 'Admin config list',
                                        'module'     => 'core',
                                        'section'    => 'admin',
                                        'package'    => 'config',
                                        'handler'    => 'list',
                                        'permission' => 'admin-core-config-list',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            AuthorizationMiddleware::class,
                                            Handler\Admin\Config\ListHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'update' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/update',
                                    'defaults' => [
                                        'title'      => 'Admin config update',
                                        'module'     => 'core',
                                        'section'    => 'admin',
                                        'package'    => 'config',
                                        'handler'    => 'update',
                                        'permission' => 'admin-core-config-update',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            AuthorizationMiddleware::class,
                                            Handler\Admin\Config\UpdateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'signature' => [
                        'type'         => Literal::class,
                        'options'      => [
                            'route'    => '/signature',
                            'defaults' => [],
                        ],
                        'child_routes' => [
                            'check'  => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/check',
                                    'defaults' => [
                                        'title'      => 'Admin signature check',
                                        'module'     => 'core',
                                        'section'    => 'admin',
                                        'package'    => 'signature',
                                        'handler'    => 'check',
                                        'permission' => 'admin-core-signature-check',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            AuthorizationMiddleware::class,
                                            Handler\Admin\Signature\CheckHandler::class
                                        ),
                                    ],
                                ],
                            ],
                            'update' => [
                                'type'    => Literal::class,
                                'options' => [
                                    'route'    => '/update',
                                    'defaults' => [
                                        'title'      => 'Admin signature update',
                                        'module'     => 'core',
                                        'section'    => 'admin',
                                        'package'    => 'signature',
                                        'handler'    => 'update',
                                        'permission' => 'admin-core-signature-update',
                                        'controller' => PipeSpec::class,
                                        'middleware' => new PipeSpec(
                                            RequestPreparationMiddleware::class,
                                            SecurityMiddleware::class,
                                            AuthenticationMiddleware::class,
                                            AuthorizationMiddleware::class,
                                            Handler\Admin\Signature\UpdateHandler::class
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ],

                    // Admin installer
                    'installer' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/installer',
                            'defaults' => [
                                'module'     => 'core',
                                'section'    => 'admin',
                                'package'    => 'installer',
                                'handler'    => 'installer',
                                'controller' => PipeSpec::class,
                                'middleware' => new PipeSpec(
                                    RequestPreparationMiddleware::class,
                                    SecurityMiddleware::class,
                                    AuthenticationMiddleware::class,
                                    InstallerMiddleware::class,
                                    Handler\InstallerHandler::class
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager'    => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
