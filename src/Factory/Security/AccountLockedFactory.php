<?php

namespace Core\Factory\Security;

use Core\Security\Account\AccountLocked;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Service\CacheService;

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
            $config
        );
    }
}