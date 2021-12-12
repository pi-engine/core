<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 * @package         View
 */

namespace Pi\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Demo extends AbstractHelper
{
    public function __invoke($string = '')
    {
        $string = !empty($string) ? $string : 'This is demo text';

        return sprintf('<div class="mb-3">%s</div>', $string);
    }
}