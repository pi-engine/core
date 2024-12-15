<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Security;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Security\Account\AccountLocked;
use Pi\Core\Security\Account\AccountLoginAttempts;
use Pi\Core\Service\CacheService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AccountLoginAttemptsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AccountLoginAttempts
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AccountLoginAttempts
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security'] ?? [];

        return new AccountLoginAttempts(
            $container->get(CacheService::class),
            $container->get(AccountLocked::class),
            $config
        );
    }
}