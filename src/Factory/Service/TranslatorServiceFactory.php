<?php

declare(strict_types=1);

namespace Pi\Core\Factory\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Pi\Core\Service\TranslatorService;
use Psr\Container\ContainerInterface;

class TranslatorServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TranslatorService
    {
        // Get config
        $config = $container->get('config');

        return new TranslatorService(
            $config['translator']
        );
    }
}