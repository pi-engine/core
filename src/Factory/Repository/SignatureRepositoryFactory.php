<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Repository\SignatureRepository;
use Pi\Core\Security\Signature;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SignatureRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return SignatureRepository
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SignatureRepository
    {
        return new SignatureRepository(
            $container->get(AdapterInterface::class),
            $container->get(Signature::class)
        );
    }
}