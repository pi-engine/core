<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Handler\Admin\Signature;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Handler\Admin\Signature\UpdateHandler;
use Pi\Core\Service\SignatureService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class UpdateHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return UpdateHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): UpdateHandler
    {
        return new UpdateHandler(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(SignatureService::class)
        );
    }
}