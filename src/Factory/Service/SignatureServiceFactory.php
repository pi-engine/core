<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Repository\SignatureRepository;
use Pi\Core\Security\Signature;
use Pi\Core\Service\SignatureService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SignatureServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return SignatureService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SignatureService
    {
        // Get config
        $config = $container->get('config');
        $config = $config['security']['signature'] ?? [];

        return new SignatureService(
            $container->get(SignatureRepository::class),
            $container->get(Signature::class),
            $config
        );
    }
}