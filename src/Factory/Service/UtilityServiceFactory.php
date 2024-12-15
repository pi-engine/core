<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class UtilityServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return UtilityService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): UtilityService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['utility'] ?? [];

        return new UtilityService($config);
    }
}