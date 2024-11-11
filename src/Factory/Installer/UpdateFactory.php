<?php

namespace Pi\Core\Factory\Installer;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Installer\Update;
use Pi\Core\Service\InstallerService;
use Psr\Container\ContainerInterface;

class UpdateFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Update
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Update
    {
        return new Update(
            $container->get(InstallerService::class)
        );
    }
}