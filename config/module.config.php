<?php

namespace Pi\Core;

return [
    'service_manager' => [
        'factories' => [
            Installer\Install::class                       => Factory\Installer\InstallFactory::class,
            Installer\Update::class                        => Factory\Installer\UpdateFactory::class,
            Installer\Remove::class                        => Factory\Installer\RemoveFactory::class,
            Security\Account\AccountLoginAttempts::class   => Factory\Security\AccountLoginAttemptsFactory::class,
            Security\Account\AccountLocked::class          => Factory\Security\AccountLockedFactory::class,
            Middleware\SecurityMiddleware::class           => Factory\Middleware\SecurityMiddlewareFactory::class,
            Middleware\InstallerMiddleware::class          => Factory\Middleware\InstallerMiddlewareFactory::class,
            Middleware\ErrorMiddleware::class              => Factory\Middleware\ErrorMiddlewareFactory::class,
            Middleware\RequestPreparationMiddleware::class => Factory\Middleware\RequestPreparationMiddlewareFactory::class,
            Service\CacheService::class                    => Factory\Service\CacheServiceFactory::class,
            Service\UtilityService::class                  => Factory\Service\UtilityServiceFactory::class,
            Service\TranslatorService::class               => Factory\Service\TranslatorServiceFactory::class,
            Service\InstallerService::class                => Factory\Service\InstallerServiceFactory::class,
            Handler\ErrorHandler::class                    => Factory\Handler\ErrorHandlerFactory::class,
        ],
    ],
    'view_manager'    => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
