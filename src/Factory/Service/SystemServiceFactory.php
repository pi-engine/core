<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\SystemService;
use Psr\Container\ContainerInterface;

class SystemServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return SystemService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SystemService
    {
        return new SystemService();
    }
}