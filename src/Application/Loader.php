<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Core\Application;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Pi\Core\Application\Service\Audit;
use Pi\Core\Application\Service\Demo;
use Pi\Core\Application\Service\File;

class Loader
{
    public function service($name = null, $options = [])
    {
        $serviceConfig = [
            'factories' => [
                Demo::class  => InvokableFactory::class,
                Audit::class => InvokableFactory::class,
                File::class  => InvokableFactory::class,
            ],
            'aliases'   => [
                'demo'  => Demo::class,
                'audit' => Audit::class,
                'file'  => File::class,
            ],
        ];

        $serviceManager = new ServiceManager($serviceConfig);
        return $serviceManager->get($name);
    }

}