<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\CsrfService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class CsrfServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return CsrfService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CsrfService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security']['csrf'] ?? [];

        return new CsrfService(
            $container->get(CacheService::class),
            $config
        );
    }
}