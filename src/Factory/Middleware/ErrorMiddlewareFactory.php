<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Middleware;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Middleware\ErrorMiddleware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ErrorMiddlewareFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return ErrorMiddleware
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ErrorMiddleware
    {
        return new ErrorMiddleware(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ErrorHandler::class)
        );
    }
}