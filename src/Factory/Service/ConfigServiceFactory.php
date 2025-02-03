<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\ConfigService;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ConfigServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ConfigService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ConfigService
    {
        // Get config
        $config = $container->get('config');
        $config = array_merge(
            $config['global'] ?? [],
                $config['media'] ?? [],
                $config['account']['password'] ?? []
        );

        return new ConfigService(
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}