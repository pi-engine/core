<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Security;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Security\Signature;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SignatureFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return Signature
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Signature
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security']['signature'] ?? [];

        return new Signature($config);
    }
}