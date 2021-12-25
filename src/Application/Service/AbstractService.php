<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Core\Application\Service;

use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

abstract class AbstractService
{
    public function __construct($options = [])
    {
        $serviceManager = new ServiceManager([
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
        ]);
    }
}