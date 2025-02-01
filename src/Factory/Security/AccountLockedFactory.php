<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Security;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Security\Account\AccountLocked;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AccountLockedFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return AccountLocked
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AccountLocked
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security'] ?? [];

        return new AccountLocked(
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}