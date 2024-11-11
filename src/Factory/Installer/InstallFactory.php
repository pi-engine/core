<?php

namespace Pi\Core\Factory\Installer;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Installer\Install;
use Pi\Core\Service\InstallerService;
use Psr\Container\ContainerInterface;

class InstallFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Install
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Install
    {
        return new Install(
            $container->get(InstallerService::class)
        );
    }
}