<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Core\Application;

use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Pi\Core\Application\Service\Encryption;
use Pi\Core\Application\Service\File;

class Loader
{
    public function service(string $name, array $options = [])
    {
        $serviceConfig = [
            'factories' => [
                Encryption::class => InvokableFactory::class,
                File::class       => InvokableFactory::class,
            ],
            'aliases'   => [
                'encryption' => Encryption::class,
                'file'       => File::class,
            ],
        ];

        $serviceManager = new ServiceManager($serviceConfig);
        return $serviceManager->get($name);
    }

}