<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Psr\Container\ContainerInterface;

class CacheServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return CacheService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CacheService
    {
        // Get config
        $config = $container->get('config');

        return new CacheService(
            $container->get(StorageAdapterFactoryInterface::class),
            $config['cache']
        );
    }
}