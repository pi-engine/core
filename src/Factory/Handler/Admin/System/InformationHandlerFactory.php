<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Handler\Admin\System;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\Admin\System\InformationHandler;
use Pi\Core\Service\SystemService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class InformationHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return InformationHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): InformationHandler
    {
        return new InformationHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(SystemService::class)
        );
    }
}