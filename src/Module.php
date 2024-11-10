<?php

namespace Pi\Core;

use Laminas\Mvc\MvcEvent;

class Module
{
    public function getConfig(): array
    {
        return include realpath(__DIR__ . '/../config/module.config.php');
    }

    public function onBootstrap(MvcEvent $event)
    {
        /*
        $application = $event->getApplication();
        $eventManager = $application->getEventManager();
        $serviceManager = $application->getServiceManager();
        */
    }
}