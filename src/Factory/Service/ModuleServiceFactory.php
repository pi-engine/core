<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ModuleManager\ModuleManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Repository\ModuleRepository;
use Pi\Core\Service\ModuleService;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ModuleServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ModuleService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ModuleService
    {
        // Get config
        $config = [];

        return new ModuleService(
            $container->get(ModuleRepository::class),
            $container->get(ModuleManager::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}