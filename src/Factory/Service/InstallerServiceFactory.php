<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\InstallerService;
use Pi\User\Service\PermissionService;
use Psr\Container\ContainerInterface;

class InstallerServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return InstallerService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): InstallerService
    {
        return new InstallerService(
            $container->get(PermissionService::class)
        );
    }
}