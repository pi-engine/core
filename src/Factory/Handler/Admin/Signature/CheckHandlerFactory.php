<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Handler\Admin\Signature;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\Admin\Signature\CheckHandler;
use Pi\Core\Service\SignatureService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class CheckHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return CheckHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): CheckHandler
    {
        return new CheckHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(SignatureService::class)
        );
    }
}