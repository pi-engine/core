<?php

namespace Pi\Core\Factory\Installer;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Installer\Remove;
use Pi\Core\Service\InstallerService;
use Psr\Container\ContainerInterface;

class RemoveFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Remove
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Remove
    {
        return new Remove(
            $container->get(InstallerService::class)
        );
    }
}