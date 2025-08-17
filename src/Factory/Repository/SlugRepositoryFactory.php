<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Repository;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Repository\SlugRepository;
use Pi\Core\Service\UtilityService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class SlugRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     *
     * @return SlugRepository
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SlugRepository
    {
        // Get config
        $config = [];

        return new SlugRepository(
            $container->get(AdapterInterface::class),
            $container->get(UtilityService::class),
            $config
        );
    }
}