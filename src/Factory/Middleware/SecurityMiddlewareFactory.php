<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Middleware\SecurityMiddleware;
use Pi\Core\Service\CacheService;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class SecurityMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return SecurityMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SecurityMiddleware
    {
        // Get config
        $config = $container->get('config');
        $config = array_merge($config['security'], $config['global']);

        return new SecurityMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(CacheService::class),
            $container->get(UtilityService::class),
            $container->get(ErrorHandler::class),
            $config
        );
    }
}